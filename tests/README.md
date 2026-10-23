# Low Options PHPUnit suite

The add-on's PHP and PHAR are immutable. Tests load the shipped PHAR through the shipped autoloader; they do not rebuild or patch it. `reports/inventory.json` inventories all 27 source units: four loose PHP files, 22 archive members, and the executable archive stub. It includes constructors, magic methods, closures, procedural/view code and source hashes.

## Run

Install pinned local tooling if needed:

```sh
./tests/.phpunit/install.sh
```

Run the complete suite with an explicit runtime:

```sh
PHP_BINARY='/Users/tomjaeger/Library/Application Support/Herd/bin/php82' ./tests/.phpunit/run-phpunit.sh -c phpunit.xml
python3 tests/scripts/run-matrix.py
```

The matrix script uses each absolute Herd binary directly. It never changes Herd's global version, configuration or services. It fails if any suite fails or executes zero tests. Each invocation creates a unique report directory. PHPUnit 9.6.38 is pinned by SHA-256 and used on all seven runtimes; the scaffold's additional PHPUnit versions are also pinned but unused.

Source diagnostics are exceptions under `E_ALL`, including diagnostics production hides with `@`. `DiagnosticHarnessTest` verifies this policy with an intentional diagnostic-handler probe. Native load/compilation tests start fresh processes under the parent `PHP_BINARY`, with explicit diagnostics and isolated `TMPDIR`/`sys_temp_dir`. They execute the normal source directly. Native probes deliberately do not merge instrumentation graphs with the parent suite.

The suite is intentionally red for documented production behavior. Do not suppress or skip these regressions to make the suite pass. See `STATUS.md` and `reports/matrix/*.txt`.

## Coverage

Collect a single runtime's raw Xdebug graph:

```sh
LOW_OPTIONS_RAW_COVERAGE=/private/tmp/low-options-coverage.json \
  '/Users/tomjaeger/Library/Application Support/Herd/bin/php82' \
  -d zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-82-arm64.so \
  -d xdebug.mode=coverage tests/.phpunit/phpunit-9.phar -c phpunit.xml

'/Users/tomjaeger/Library/Application Support/Herd/bin/php82' \
  tests/scripts/inventory.php tests/reports/inventory.json tests/reports/measurement

python3 tests/scripts/report-coverage.py /private/tmp/low-options-coverage.json \
  tests/reports/coverage.json --measurement tests/reports/measurement \
  --clover /private/tmp/low-options-lines.xml

python3 /Users/tomjaeger/.codex/skills/ee-addon-phpunit-coverage-writer/scripts/addon_method_coverage_report.py \
  --repo-root "$PWD" --source-root tests/reports/measurement/low_options \
  --include-magic --reuse-clover --coverage-clover /private/tmp/low-options-lines.xml \
  --json
```

Measurement copies are verified byte-identical to their shipped source and are used only for source parsing/reporting. Execution uses original files and the archive. `reports/skill-method-lines.json.gz` is the skill helper's line-only report; its branch metrics are unknown, and its regex parser can mistake JavaScript function text in the archive licensing heredoc for PHP methods. The PHP-token inventory and `coverage.json` disambiguate those cases.

`reports/xdebug-php82.json.gz` retains the complete parent-process source graph, including `out`/`out_hit`. `coverage.json` reports each method's lines, `basic_block` hits and decision exits separately. A basic-block hit is not proof of both conditional outcomes. Synthetic terminal exits remain separately visible; unexecuted source remains unknown rather than 100%. No incompatible runtime, source or child-process graphs are merged. See the [Xdebug API documentation](https://xdebug.org/docs/code_coverage).

## Framework seams and limitations

Template's missing parameter defaults to `false`, matching EE's `fetch_param`. Hydrated ChannelField settings are arrays. Model field/query methods are recorded and asserted, and category result `Collection::pluck` follows EE's array/object extraction contract. Database query results distinguish no row, zero rows and hydrated rows. Redirect fixtures terminate flow with a dedicated exception where EE terminates the request.

These seams do not replace integration testing against a running EE database or browser. EE2 bridge methods are exercised as shipped classes; version-dependent behavior additionally needs separate runtime contexts. The suite covers meaningful behavior, not every potential invalid type accepted by untyped historical PHP methods. Remaining gaps are explicit in `STATUS.md` and the inventory/coverage reports.

The PHAR stub changes `include_path`, registers an additional autoloader and calls `Phar::interceptFileFuncs` during discovery, before individual tests. Request globals, include paths and newly registered autoloaders are restored per test; initial include path/autoloaders are also restored at process shutdown. PHP class declarations and `Phar::interceptFileFuncs` cannot be undone safely in this process. Complete runs therefore use a fresh CLI process, and native compilation probes use dedicated child processes; this is not proof that PHAR interception is reversible in an existing EE request.

The direct private `_explode_param(null)` vector is a robustness/compatibility expectation for an unsupported null input (its source docblock requires a string). A missing EE template parameter actually returns `false`. Cache null/scalar vectors similarly record precise observed loss and robustness expectations; documented array inputs, including empty arrays, have the stronger public-contract basis.
