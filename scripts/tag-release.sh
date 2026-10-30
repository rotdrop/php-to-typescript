#!/usr/bin/env bash
# Usage: tag-release.sh [remote]
#
# Determines the top-most release version (X.Y.Z or X.Y.Z-rcN) in
# CHANGELOG.md at HEAD. If no tag vX.Y.Z[-rcN] exists for it, asks for
# confirmation, then creates an annotated tag on HEAD, validates it with
# check-changelog.sh and pushes it to <remote> (default: origin).
set -euo pipefail

remote="${1:-origin}"
changelog="CHANGELOG.md"

cd "$(git rev-parse --show-toplevel)"

version="$(git show "HEAD:$changelog" | awk '
  match($0, /^## \[?[0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?\]?([[:space:]]|$)/) {
    v = $0
    sub(/^## \[?/, "", v)
    sub(/[^0-9.a-z-].*$/, "", v)
    print v
    exit
  }
')"

if [[ -z "$version" ]]; then
  echo "No release section '## [X.Y.Z]' or '## [X.Y.Z-rcN]' found in $changelog at HEAD" >&2
  exit 1
fi
tag="v$version"
echo "Top-most release in $changelog: $version"

if git rev-parse -q --verify "refs/tags/$tag" >/dev/null; then
  echo "Tag $tag already exists locally; nothing to do."
  exit 0
fi
if git ls-remote --exit-code --tags "$remote" "refs/tags/$tag" >/dev/null 2>&1; then
  echo "Tag $tag already exists on $remote; nothing to do."
  exit 0
fi

if ! git diff --quiet HEAD -- || [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
  echo "Warning: working tree has uncommitted changes; the tag will point to HEAD."
fi

head_desc="$(git log -1 --format='%h %s' HEAD)"
read -r -p "Create tag $tag on $head_desc and push it to $remote? [y/N] " answer
if [[ ! "${answer,,}" =~ ^y(es)?$ ]]; then
  echo "Aborted."
  exit 1
fi

git tag -a "$tag" -m "Release $tag" HEAD
if ! scripts/check-changelog.sh "$tag"; then
  git tag -d "$tag" >/dev/null
  echo "Changelog check failed; removed local tag $tag" >&2
  exit 1
fi
git push "$remote" "refs/tags/$tag"
echo "Pushed $tag to $remote."
