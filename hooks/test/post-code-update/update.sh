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

# Alias for current site and current environment.
alias=$site.$target_env

# Shut down current site
drush @$alias state-set system.maintenance_mode 1 --format=integer

# Run the usual things to refresh site with new code.
drush @$alias cr --yes
drush @$alias updb --yes
drush @$alias cim sync --yes
drush @$alias cr --yes

# Open to the public again.
drush @$alias state-set system.maintenance_mode 0 --format=integer
