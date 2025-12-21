#!/usr/bin/env bash
set -euo pipefail

# Simple helper to add, commit, and push all changes
# Usage:
#   ./git_push.sh "your commit message"
# If no message is provided, a default message with timestamp will be used.

# Ensure we're in a git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: Not inside a git repository." >&2
  exit 1
fi

# Determine current branch
current_branch=$(git rev-parse --abbrev-ref HEAD)
if [[ "$current_branch" == "HEAD" || -z "$current_branch" ]]; then
  current_branch="main"
fi

# Ensure remote 'origin' exists
if ! git remote get-url origin >/dev/null 2>&1; then
  echo "Error: No remote 'origin' configured." >&2
  echo "Set it with: git remote add origin <your-repo-url>" >&2
  exit 1
fi

# Stage all changes
git add -A

# Build commit message
if [[ $# -gt 0 ]]; then
  commit_msg="$*"
else
  commit_msg="chore: push all changes ($(date -Iseconds))"
fi

# Commit only if there are staged changes
if git diff --cached --quiet; then
  echo "No changes to commit. Skipping commit step."
else
  git commit -m "$commit_msg"
fi

# Check if upstream is set
if git rev-parse --abbrev-ref --symbolic-full-name @{u} >/dev/null 2>&1; then
  echo "Upstream detected. Pushing to tracked branch..."
  git push
else
  echo "No upstream. Pushing to origin/$current_branch and setting upstream..."
  git push -u origin "$current_branch"
fi

 echo "Done. Current status:" 
 git status --short
