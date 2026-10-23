#!/bin/sh
#
# Cloud Hook: post-code-update
#
# The post-code-update hook runs in response to code commits.
# When you push commits to a Git branch, the post-code-update hooks runs for
# each environment that is currently running that branch. See
# ../README.md for details.
#
# Usage: post-code-update site target-env source-branch deployed-tag repo-url
#                         repo-type

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"

# Stop on the first failure, so a failed update or import leaves the site in
# maintenance mode instead of serving new code on old config.
set -e

# Use the site-local Drush from the deployed artifact, not the global drush9.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

# Run updates and import config.
drush sset system.maintenance_mode 1 --yes
drush deploy --yes
drush sset system.maintenance_mode 0 --yes
