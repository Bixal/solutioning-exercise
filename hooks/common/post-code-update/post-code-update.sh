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

echo "$site.$target_env: The $source_branch database migrations will now be run on $target_env."
drush @$site.$target_env updatedb --yes
echo "$site.$target_env: The $source_branch database migrations were succesfully run on $target_env."

echo "$site.$target_env: The $source_branch database entities  will now be updated on $target_env."
drush @$site.$target_env entup --yes
echo "$site.$target_env: The $source_branch database entities were succesfully updated on $target_env."

echo "$site.$target_env: The $source_branch configuration will now be updated on $target_env."
drush @$site.$target_env cim vcs --yes
echo "$site.$target_env: The $source_branch configurations were succesfully updated on $target_env."

echo "$site.$target_env: The $source_branch branch has been updated on $target_env. Clearing the cache."
drush @$site.$target_env cr --yes
echo "$site.$target_env: The $source_branch branch has been updated on $target_env."