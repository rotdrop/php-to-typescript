#!/usr/bin/env bash
# Usage: check-changelog.sh <tag> [notes-output-file]
#
# Verifies that CHANGELOG.md contains a section for the version of <tag>
# (vX.Y.Z or vX.Y.Z-rcN) and that CHANGELOG.md changed since the previous
# tag. Writes the section body to the notes file if given.
set -euo pipefail

tag="${1:?usage: $0 <tag> [notes-file]}"
notes_file="${2:-}"
changelog="CHANGELOG.md"

if [[ ! "$tag" =~ ^v([0-9]+\.[0-9]+\.[0-9]+(-rc[0-9]+)?)$ ]]; then
  echo "::error::Tag '$tag' does not match vX.Y.Z or vX.Y.Z-rcN" >&2
  exit 1
fi
version="${BASH_REMATCH[1]}"

if [[ ! -f "$changelog" ]]; then
  echo "::error::$changelog not found" >&2
  exit 1
fi

# Extract the body of "## [<version>]" (brackets optional) up to the next "## ".
notes="$(awk -v ver="$version" '
  BEGIN { esc = ver; gsub(/\./, "\\.", esc) }
  /^## / {
    if (found) exit
    if ($0 ~ "^## \\[?" esc "\\]?([[:space:]]|$)") { found = 1; next }
  }
  found { print }
' "$changelog")"

if [[ -z "${notes//[[:space:]]/}" ]]; then
  echo "::error file=$changelog::No non-empty '## [$version]' section found in $changelog" >&2
  exit 1
fi

previous_tag="$(git describe --tags --abbrev=0 --match 'v[0-9]*' "${tag}^" 2>/dev/null || true)"
if [[ -n "$previous_tag" ]]; then
  if git diff --quiet "$previous_tag" "$tag" -- "$changelog"; then
    echo "::error file=$changelog::$changelog was not updated since $previous_tag" >&2
    exit 1
  fi
  echo "$changelog updated since $previous_tag"
else
  echo "No previous release tag found; skipping change check"
fi

echo "Found changelog section for $version"
if [[ -n "$notes_file" ]]; then
  printf '%s\n' "$notes" > "$notes_file"
fi
