#!/usr/bin/env bash
set -euo pipefail
script_dir="$(cd "$(dirname "$0")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
php_binary="${PHP_BINARY:-/Users/tomjaeger/Library/Application Support/Herd/bin/php82}"
expected=6b5235f282c6ccab3674ac27c5b1ddbd1da838e74bf07002ddcfd030d31298d7
actual="$(shasum -a 256 "$script_dir/phpunit-9.phar" | awk '{print $1}')"
if [[ "$actual" != "$expected" ]]; then
  echo 'PHPUnit checksum mismatch; run tests/.phpunit/install.sh.' >&2
  exit 2
fi
cd "$repo_root"
requested_report=''
previous=''
for argument in "$@"; do
  case "$argument" in
    --help|-h|--version|--manifest|--sbom|--composer-lock|--list-tests|--list-tests-xml|--list-tests-xml=*|--list-groups|--list-suites)
      exec "$php_binary" -d error_reporting=-1 -d display_errors=1 "$script_dir/phpunit-9.phar" "$@"
      ;;
    --log-junit=*)
      requested_report="${argument#--log-junit=}"
      ;;
  esac
  if [[ "$previous" == '--log-junit' ]]; then
    requested_report="$argument"
  fi
  previous="$argument"
done
report_dir="$(mktemp -d "${TMPDIR:-/private/tmp}/low-options-phpunit.XXXXXX")"
report="$report_dir/result.xml"
trap 'rm -f "$report"; rmdir "$report_dir"' EXIT
set +e
"$php_binary" -d error_reporting=-1 -d display_errors=1 "$script_dir/phpunit-9.phar" "$@" --log-junit "$report"
status=$?
set -e
"$php_binary" "$script_dir/validate-report.php" "$report"
if [[ -n "$requested_report" ]]; then
  cp "$report" "$requested_report"
fi
exit "$status"
