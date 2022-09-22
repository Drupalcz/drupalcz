#!/usr/bin/env bash

# Sync tags from the hosting provider repo to this github repo.


# Fail immediately when any command fails:
set -e

# Debug output?
if [ "${DEBUG}" == "true" ]; then
  set -x
  env
fi

# Verify expected variables are defined:
test -n "${GIT_REMOTE}"
test -n "${GIT_SSH_PRIVATE_KEY}"

# Verify we have this github repo checked out, exit if not:
HAS_CHECKED_OUT_REPO="$(git rev-parse --is-inside-work-tree 2>/dev/null || /bin/true)"
[[ "${HAS_CHECKED_OUT_REPO}" == "true" ]] || exit 1


# Set up private ssh key for git
mkdir ~/.ssh
chmod 700 ~/.ssh
echo "${GIT_SSH_PRIVATE_KEY}" > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

git config --global core.sshCommand "ssh -i ~/.ssh/id_rsa -o IdentitiesOnly=yes -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

git remote add hosting "${GIT_REMOTE}"

# fetch tags from the hosting provider repo:
git fetch --tags hosting
# push them to the github repo (which we have checked out).
git push --tags
