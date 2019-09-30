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

# Update
alias=$site.$target_env

# Run updates and import config.
drush9 @$alias sset system.maintenance_mode 1 --yes
drush9 @$alias cr --yes
drush9 @$alias updb --yes
drush9 @$alias cim sync --yes
drush9 @$alias cr --yes
drush9 @$alias sset system.maintenance_mode 0 --yes
