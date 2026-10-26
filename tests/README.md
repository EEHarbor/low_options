# Low Options PHPUnit suite

Current source scope and strict command: see [../PHP_COMPATIBILITY_2026.md](../PHP_COMPATIBILITY_2026.md). The original Foundation PHAR/loader are restored to develop; the only retained production diff is the add-on plugin. The current source inventory has 27 units: four loose PHP files, 22 archive members and the stub. No new shared factory or extracted Foundation build source is shipped.

Every original configured suite, provider and test remains selected. The complete matrix can remain red on the restored shared dependency. Keep strict startup, prethrow journals, native outcome/capture checks and all original failure assertions. Historical matrices and coverage below are preserved history, not current acceptance or a coverage-completion claim.

```sh
PHP_BINARY='/Users/tomjaeger/Library/Application Support/Herd/bin/php74' ./tests/.phpunit/run-phpunit.sh -c phpunit.xml --do-not-cache-result
```

Repeat for php80–php85 under the campaign lease. Generic caught/provider/native authority controls now execute this add-on's metadata entry point; deliberately faulty controls earn only harness rejection evidence. The scope-correction review packet records exact inputs, preserved selections, full streams and shared dependency failures. No publication approval is claimed.

## Historical tests-only documentation

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

The `ee('db')` service resolves a fresh builder, matching current EE's `Database::newQuery`. Builders share configured backend responses and a query journal while preserving separate pending calls and schema caches. Settings tests observe the actual isolated write and verify that an unfinished primary query survives. This seam models the used contract without opening a database connection.

The PHAR stub changes `include_path`, registers an additional autoloader and calls `Phar::interceptFileFuncs` during discovery, before individual tests. Request globals, include paths and newly registered autoloaders are restored per test; initial include path/autoloaders are also restored at process shutdown. PHP class declarations and `Phar::interceptFileFuncs` cannot be undone safely in this process. Complete runs therefore use a fresh CLI process, and native compilation probes use dedicated child processes; this is not proof that PHAR interception is reversible in an existing EE request.

The direct private `_explode_param(null)` vector is a robustness/compatibility expectation for an unsupported null input (its source docblock requires a string). A missing EE template parameter actually returns `false`. Cache null/scalar vectors similarly record precise observed loss and robustness expectations; documented array inputs, including empty arrays, have the stronger public-contract basis.

The installed-custom-nav cases create a unique native CP request with a test-only extension fixture and symlinks to unchanged shipped files. They retain the stock missing-nav regression. To measure those valid contexts in one fresh PHP82 process:

```sh
LOW_OPTIONS_NAV_COVERAGE=/private/tmp/low-options-nav.json \
  '/Users/tomjaeger/Library/Application Support/Herd/bin/php82' \
  -d zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-82-arm64.so \
  -d xdebug.mode=coverage tests/scripts/nav-probe.php all

python3 tests/scripts/report-coverage.py /private/tmp/low-options-nav.json \
  tests/reports/nav-coverage.json --measurement tests/reports/measurement \
  --clover /private/tmp/low-options-nav-lines.xml
```

The raw native artifact and per-method report stay separate from parent-process coverage. Their filter includes only the original PHAR's Conduit/McpNav.php; the custom extension fixture and its methods are excluded. This report currently reaches82/84 lines and47/54 decision outcomes, not100% coverage.

The service-registration fixture now follows the current EE Facade's existing-service guard, explicit null registrations and complete removal. Modern bridge classes are tested against that current contract; these cases do not prove historical EE3/4/5 runtime behavior. The bridge's advertised overwrite operation remains red for an already-loaded service. A separate read-only integration probe accepts an actual EE source directory:

```sh
'/Users/tomjaeger/Library/Application Support/Herd/bin/php82' \
  -d error_reporting=-1 -d display_errors=1 \
  tests/scripts/probe-real-ee-facade.php \
  /Users/tomjaeger/Sites/ee_repo_2/system/ee
```

The probe declares APP_VER=7.5.0, loads the real Facade and unchanged add-on bridge, and records source hashes and the retained original object after replacement fails. It also verifies removal. Exit1 reports the source regression. This does not boot a full EE request, and external framework source is excluded from add-on coverage. All seven runtime results are retained in `reports/real-facade-probe.json`.

Persistent-cache vectors distinguish documented array/object payloads and compound keys from scalar/null robustness inputs. Typed values round-trip before and after clearing request-local state. Two retained regressions expose array-key retrieval and a literal SHA-1 key colliding with another entry's internal static-cache lookup.

Filesystem-cache tests use actual read-only directories and assert exact logging and no-write behavior. Permission tests require a nonprivileged user, as used by the required Herd matrix; root CI is not proven. Teardown restores only that process's fixture paths and permissions. The file-helper seam matches EE's suppressed fopen/false return and locked writes. Shipped source diagnostics remain strict, including blocked mkdir and malformed-cache regressions. Processed-key existence also remains red. Current cache coverage is 64/70 lines and 33/36 decision outcomes; remaining diagnostic and instrumentation constraints are listed in STATUS.

Version payload tests import a generated test RSA key and decrypt the actual footer ciphertext after configuring one instance's public key. All OpenSSL operations and real updater construction execute in disposable native processes to preserve the caller's error queue; empty and pre-seeded FIFO sentinels verify that boundary. Native helper output files avoid pipe deadlock and a 20-second deadline bounds each child. They assert typed JSON values independently of member order and CSS delimiters. The installed-Pro payload regression remains red; the absent-Pro control passes. A global updater fixture models EE's class naming and exact module registration, and fieldtype cases cover create-entry URI fallback. These unit contexts leave native CP/legacy integration work explicit in STATUS.

Six separate native crypto graphs can be collected alongside the parent without merging them:

```sh
LOW_OPTIONS_RAW_COVERAGE=/private/tmp/new-parent.json \
LOW_OPTIONS_CRYPTO_COVERAGE=/private/tmp/new-crypto-prefix \
LOW_OPTIONS_CRYPTO_XDEBUG=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-82-arm64.so \
'/Users/tomjaeger/Library/Application Support/Herd/bin/php82' \
  -d zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-82-arm64.so \
  -d xdebug.mode=coverage tests/.phpunit/phpunit-9.phar -c phpunit.xml
```

Use fresh absolute paths. The queue sentinel's nested calls do not collect redundant graphs. `xdebug-crypto-php82.json.gz` preserves each raw JSON string and SHA256; `crypto-coverage.json.gz` records every unit/method separately. Parent coverage decreases after crypto isolation, and no cross-process total is inferred.

Stub helper tests call the actual Extract_low_options class after normal PHAR loading. They compare every original manifest/member with native Phar, exercise plain/gzip/bzip2 binary read boundaries, and restore TMPDIR/CWD in finally. The recursive nonempty-tree cleanup and dangling-link robustness diagnostics remain source-red cases. Encoded incompressible gzip/bzip2 fixtures also reach the full source read chunk. Pure helper coverage does not establish stream-free loader or Windows integration.


NativeProcess shares the crypto helper's bounded file-backed subprocess boundary with three original-stub terminal contexts. Runner contracts cover 1 MiB independent stdout/stderr, invalid timeouts, a stalled child's deadline/owned cleanup and strict @ diagnostics. Size-message formatting remains red at original stub262; valid-return and checksum-termination controls pass. Collect the three graphs alongside the documented full raw run by setting LOW_OPTIONS_STUB_COVERAGE to a fresh absolute prefix and LOW_OPTIONS_STUB_XDEBUG to the same process-local Xdebug extension. xdebug-stub-php82.json.gz retains exact raw bytes/provenance; stub-coverage.json.gz reports all27 units/methods separately for each context. Six crypto graphs are freshly rerun after this shared-runner change. Installed-nav coverage carries forward unchanged, separate from every new graph.


Legacy/state probes import original AbstractBridge/StaticCache/EE2 entries directly with separate historical APP_VER constants and an owned temporary view directory. They do not boot historical EE or resolve the package loader. NativeLoadTest and ArchiveNavigationTest now use the shared bounded NativeProcess boundary. Database journals bind exact settings write traces/cache snapshots to their owning clone or fresh builder while preserving primary state.

Collect20 native legacy graphs per runtime by setting LOW_OPTIONS_LEGACY_COVERAGE to a fresh prefix and LOW_OPTIONS_LEGACY_XDEBUG to the matching process-local Xdebug extension. The normal complete PHP82 raw suite collects the20 PHP82 contexts; a standalone ArchiveLegacyStateTest run collects20 PHP74 contexts. Each raw report has a .context companion with options and source/runtime/raw hashes. xdebug-legacy-php74/82.json.gz retain exact raw bytes and metadata; legacy-php74/82-coverage.json.gz retain separate complete per-unit/method reports. Parent, crypto, terminal, legacy and navigation graphs stay separate. tests/REMAINING.md is the incomplete-scope handoff.
