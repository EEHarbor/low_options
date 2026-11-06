#!/usr/bin/env bash
set -euo pipefail
script_dir="$(cd "$(dirname "$0")" && pwd)"
while read -r version filename expected; do
  download="$(mktemp "$script_dir/.download.XXXXXX")"
  trap 'rm -f "$download"' EXIT
  curl --fail --location --silent --show-error "https://phar.phpunit.de/phpunit-${version}.phar" -o "$download"
  actual="$(shasum -a 256 "$download" | awk '{print $1}')"
  if [[ "$actual" != "$expected" ]]; then
    echo "Checksum mismatch for $filename" >&2
    exit 2
  fi
  mv "$download" "$script_dir/$filename"
  trap - EXIT
done < "$script_dir/versions.lock"
