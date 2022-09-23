#!/usr/bin/env bash

# Build artifact from the github repo and push it to the hosting provider remote repo.


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
test -n "${GH_EVENT_REF}"

# Set up private ssh key for git
mkdir ~/.ssh
chmod 700 ~/.ssh
echo "${GIT_SSH_PRIVATE_KEY}" > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

git config --global core.sshCommand "ssh -i ~/.ssh/id_rsa -o IdentitiesOnly=yes -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"

echo "GITHUB_EVENT_NAME: $GITHUB_EVENT_NAME"
echo "GITHUB_REF: $GITHUB_REF"
echo "GH_EVENT_REF: $GH_EVENT_REF"
echo "GH_EVENT_REF_TYPE: ${GH_EVENT_REF_TYPE}"

# Prepare variables.
FOLDER_GITHUB=$(pwd)
PROJECT_NAME="$(basename `pwd`)"
FOLDER_HOSTING="$FOLDER_GITHUB/../hosting"
mkdir -p $FOLDER_HOSTING
echo "# Github folder: $FOLDER_GITHUB"
echo "# Hosting folder: $FOLDER_HOSTING"

# Figure out who is building.
shalite=$(git rev-parse --short HEAD)
gitname=$(git show -s --format='%an' HEAD)
gitemail=$(git show -s --format='%ae' HEAD)
commitmessage=$(git log -1 --pretty=%B)
if [ "$gitname" == "" ]; then
  gitname="Morpht Automation (GitHub Actions)"
  gitemail="deployer@morpht.com"
fi
echo "# Name: $gitname"
echo "# Mail: $gitemail"
echo "# SHA: $shalite"
git config --global core.excludesfile false
git config --global core.fileMode true
git config --global user.name "${gitname}"
git config --global user.email "${gitemail}"

# Handling DELETE operation
# This handles deletions of both branches and tags
if [ "$GITHUB_EVENT_NAME" == "delete" ]; then
  set -x
  cd "$FOLDER_HOSTING"
  git clone "$GIT_REMOTE" .
  git push --delete origin "${GH_EVENT_REF}"
  { [ "${DEBUG}" ] || set +x; } 2>/dev/null
  exit 0
fi

# Assert. We expect the "push" even here, no other events.
if [ "$GITHUB_EVENT_NAME" != "push" ]; then
  echo "Unexpected GITHUB_EVENT_NAME: $GITHUB_EVENT_NAME"
  exit 1
fi

# The Build

# Install dependencies.
set -x
composer install --prefer-dist --no-progress --no-suggest
{ [ "${DEBUG}" ] || set +x; } 2>/dev/null

# Get current branch name.
BRANCHNAME="$(git rev-parse --symbolic-full-name --abbrev-ref HEAD)"
if [ "$BRANCHNAME" == "HEAD" ]; then
  # We are not on a branch, get short commit hash instead.
  BRANCHNAME="$(git rev-parse --short HEAD)"
fi
echo "# Resolved branch for a build: $BRANCHNAME."

# Hosting branch name.

# Pantheon requires valid domain name and max 11 characters.
## Replace underscores.
#BRANCHNAME_HOSTING="${BRANCHNAME//_/-}"
## Remove project name from the start
#prefix="${PROJECT_NAME}-"
#BRANCHNAME_HOSTING="${BRANCHNAME//$prefix/}"
## Strip all invalid characters.
#BRANCHNAME_HOSTING="${BRANCHNAME_HOSTING//[^a-zA-Z0-9\-]/}"
## Get first 11 characters.
#BRANCHNAME_HOSTING="$(echo ${BRANCHNAME_HOSTING:0:11})"

# Keep names 1:1 for now to make sure branch delete above works.
# @ToDo: Make this conditional based on external variable
#        to allow multiple hosting targets.
BRANCHNAME_HOSTING="$BRANCHNAME-build"
echo "# Hosting branch for a build: $BRANCHNAME_HOSTING."

# Get artefact repo.
echo "FOLDER_HOSTING: $FOLDER_HOSTING"
echo "GIT_REMOTE: $GIT_REMOTE"
echo "BRANCHNAME_HOSTING: $BRANCHNAME_HOSTING"
cd "$FOLDER_HOSTING"
git clone "$GIT_REMOTE" .
git checkout "${BRANCHNAME_HOSTING}" || git checkout -b "${BRANCHNAME_HOSTING}"

# Remove .git and ignores to make sure everything gets committed.
cd $FOLDER_GITHUB
(find . -type d -name ".git" && find . -name ".gitignore" && find . -name ".gitmodules") | xargs rm -rfv

# Swap out .git folders
cp -r "${FOLDER_HOSTING}/.git" .

# Commit new artefact.
git add -A .
git commit -m "${commitmessage}" -m "GitHub build of ${BRANCHNAME} (${BRANCHNAME_HOSTING}) @ ${shalite}." --no-gpg-sign

# Show what changed and push
git show --stat

# Handling PUSH operation

# Handling a tag
if [[ "$GH_EVENT_REF" =~ ^refs\/tags ]]; then
  tag_name=${GH_EVENT_REF:10} # cut off the first 10 chars to get tag name
  set -x
  git push --force origin $tag_name
  { [ "${DEBUG}" ] || set +x; } 2>/dev/null
  exit 0
fi

# HEAD will push the current branch
set -x
git push origin "${BRANCHNAME_HOSTING}" --force
{ [ "${DEBUG}" ] || set +x; } 2>/dev/null
exit 0
