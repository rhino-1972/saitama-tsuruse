#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
local_root="/Users/toshiyuki/Local Sites/shorinjikempo/app/public"
content_root="${repo_root}"
if [[ -d "${repo_root}/wp-content" ]]; then
  content_root="${repo_root}/wp-content"
fi

targets=(
  "mu-plugins"
  "themes/doin"
  "themes/jsc"
  "themes/Saitama Tsuruse"
)

for target in "${targets[@]}"; do
  source_path="${content_root}/${target}"
  destination_path="${local_root}/wp-content/${target}"

  if [[ ! -e "${source_path}" ]]; then
    echo "Skip: ${target} is missing in repository."
    continue
  fi

  mkdir -p "$(dirname "${destination_path}")"
  cp -R "${source_path}" "$(dirname "${destination_path}")"
  echo "Synced: ${target}"
done
