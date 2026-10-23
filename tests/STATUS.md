# Low Options campaign status

Status: **incomplete, independently reviewed Facade/cache checkpoint published**. The prior installed-nav checkpoint was `2a820c9c0c14e896aafb00c15a4df1727379b9cf`. Production defects and instrumentation/source constraints prevent a green 100% line-and-outcome milestone. Useful remaining coverage work is listed separately; this is not a claim that every uncovered method is blocked.

## Baseline and scope

- Repository: `https://github.com/EEHarbor/low_options.git`; origin is the only publication destination.
- Fresh `git fetch origin`, then clean `develop` and `git pull --ff-only origin develop` succeeded October 23, 2026. Initial sandbox DNS failed for the pull; the authorized retry succeeded.
- Pinned baseline: `ffa06e5eed4ea35657d3175a00fc82a297f8b912`; verified `HEAD...origin/develop` = `0 0` before creating `unit-tests-2026`.
- Production scope: all `low_options/` loose PHP, PHAR members and executable PHAR stub. `reports/inventory.json` records 27 PHP source units and their hashes, including unexecuted source. No source, archive, template or runtime-dependency changes.
- No repository AGENTS.md was present. Campaign and user-provided coding conventions apply.
- Read EE source for `Template::fetch_param`, Collection::pluck and EE_Fieldtype. Read the separately owned EE compatibility checkout's bootstrap for realistic isolation patterns; no files there were modified.

## Tooling and baseline coverage

The mandatory skill preflight initially found no harness or PHAR tools. Scaffold/bootstrap then passed with `PHPUNIT_COMMAND=./tests/.phpunit/run-phpunit.sh -c phpunit.xml`. Local PHARs and exact checksums are pinned in `.phpunit/versions.lock`. The launcher uses PHPUnit 9.6.38 on explicit Herd runtimes. Global Herd state/configuration was never changed.

The pre-edit skill coverage command with PHP82/Xdebug found `No tests executed!`, then the unconfigured EE direct-access guard terminated report generation. That zero-test result was rejected as a baseline measurement; the source inventory records the initially untested scope. The final skill command is documented in README; line-only Clover is derived from a single original-source raw Xdebug run and verified identical measurement copies, never from modified source.

## Final complete-suite matrix

Commands and complete output are retained in `reports/matrix.json` and `reports/matrix/*.txt`. Each command runs all configured tests with `error_reporting=-1`, `display_errors=1` and strict source diagnostics, including those hidden by `@`. Child probes inherit the intended runtime and diagnostic settings and use isolated temporary paths. Ordinary launcher runs require a fresh, nonempty JUnit report; a deliberately absent filter was rejected with exit3. Matrix runs use the checksum-protected launcher.

| Herd PHP | Cases | Assertions | Errors | Failures | Warnings | Risky | Skipped | Incomplete |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 7.4.33 | 232 | 964 | 9 | 13 | 0 | 0 | 0 | 0 |
| 8.0.30 | 232 | 962 | 10 | 11 | 0 | 0 | 0 | 0 |
| 8.1.33 | 232 | 961 | 11 | 11 | 0 | 0 | 0 | 0 |
| 8.2.30 | 232 | 957 | 12 | 11 | 0 | 0 | 0 | 0 |
| 8.3.28 | 232 | 957 | 12 | 11 | 0 | 0 | 0 | 0 |
| 8.4.15 | 232 | 957 | 12 | 11 | 0 | 0 | 0 | 0 |
| 8.5.0 | 232 | 957 | 12 | 11 | 0 | 0 | 0 | 0 |

All commands exit 2 due to retained source regressions. The matrix orchestrator exits 1 when any suite is red. PHP82 random seed 20261023 and reverse-order runs reproduce the same 232 cases, 957 assertions, 12 errors and 11 failures. All 25 test/support PHP files lint on PHP74 and PHP85. No tool/harness deprecations remain; Reflection accessibility changes are guarded below PHP8.1.

## Confirmed red source observations

Locations are relative to the shipped archive unless a loose path is shown. Tests assert intended observable behavior and retain source errors.

- `Base/Mcp.php:165`: paginateModel creates undeclared `$data`, producing a dynamic-property deprecation on PHP8.2–8.5. PHP7.4–8.1 verify the resulting limit/offset and pagination value.
- `Base/Pi.php:30`: static `info()` dereferences `$this` and returns no metadata array. This stops the method before the remaining metadata expressions.
- `Base/Ext.php:187-190`: legacy `register_extension` resets explicit hook/priority/enabled arguments. Custom hook expected; handler name actually inserted.
- `Bridge/EE2.php:47`: invalid `version_compare` operator `=<` throws on PHP8. The bridge's persistent `delete` also leaves the persistent cache value available (`cache` around lines 271–280).
- `Conduit/StaticCache.php:18`: stored empty arrays (documented array input), scalar zero/string zero/empty string and null return false due to `empty()`. Scalar/null vectors are precise robustness expectations rather than externally established supported input contracts; scalar zero also occurs in the shipped Version cache path. A separate `has()` contract remains available.
- `Conduit/PersistentCache.php:21`: documented array keys are sent to the already-processed StaticCache lookup, causing an illegal-offset diagnostic/error. A literal SHA-1 string key can instead return another logical key's value from that raw lookup at line24. Both are retained independently of the existing empty-value StaticCache regressions.
- `Bridge/EE3.php:344`, `EE4.php:344`, and `EE5.php:344` (inherited by EE6): the advertised overwrite helper calls the current Facade's guarded `set()` without removing an already-loaded service. Four current-contract class vectors remain red; a separate original-archive/real-Facade probe verifies the actual EE7-selected bridge failure on all seven runtimes. Historical EE3/4/5 integration is not claimed.
- `Conduit/GlobalFieldSettings.php:21,33`: missing named settings and malformed legacy serialized settings emit `@`-hidden undefined-key/unserialize diagnostics.
- `Base/Upd.php:64`: updating this add-on includes its absent optional extension under `@`, causing a retained strict diagnostic.
- `low_options/pi.low_options.php:189`: `show_empty=no` removes an existing option with label/value zero through `array_filter`. PHP74 additionally loses numeric-zero options through loose comparison against the default ignore token at lines 220–221; two independent behavior vectors retain that failure.
- `low_options/pi.low_options.php:398`: a direct private null-input robustness probe (source contract is string; EE missing parameters return false) to `_explode_param` emits a substr deprecation on PHP8.1–8.5.
- `Conduit/McpNav.php:7`: native loading includes an absent add-on nav file under `@`. The strict native load probe fails before usable navigation testing in the stock package.
- PHAR stub line177: no-PHAR extraction attempts `mkdir` for an already-created directory under `@` even with a fresh isolated TMPDIR. The strict native fallback probe stays red. The archive was not rebuilt.

These source behaviors were reproduced across the full matrix and in focused runs. They are not permission to fix production source.

## Coverage evidence and hard constraints

`reports/coverage.json.gz` provides exact unexecuted lines and unobserved outgoing exits per PHP method/function/file. `reports/xdebug-php82.json.gz` preserves raw graph provenance; inventory hashes tie it to the pinned source. The snapshot uses PHP8.2.30 / Xdebug3.5.3 and does not merge child/native graphs.

The main plugin reaches every executable method line and method basic block. Its file measurement is 134/135 lines; the remaining parent-process line is the direct-access exit at line4, whose behavior is separately verified in a native child. **Decision outcomes remain 97/106 for the file, not 100%.** Unobserved method exits are at foreach initialization lines115,169,174,200,209,342 and426 despite empty/nonempty vectors reaching their loop-fetch exits. These VM initialization/error edges and synthetic function-exit edges remain explicit instrumentation limitations, not proof of covered branch outcomes. The file's top-level conditional is also outside the merged parent measurement.

Other hard constraints:

- `FluxCapacitor::L` is shipped constant false. The positive licensing-JavaScript path in `Version::check` and the forced-license path in `McpNav` cannot execute without changing source. The constant was not changed.
- `Base/Mcp::paginateModel` contains a PHP_VERSION_ID < 50306 path unreachable on the required PHP7.4–8.5 matrix.
- Static `Pi::info`, the strict optional-file includes and extraction failure stop their affected paths. Repeated native/complete-suite runs confirm them.
- Native EE2–EE7 selection probes test actual conditional declarations in separate processes. Their compiled graphs are intentionally not merged across incompatible class/runtime contexts.
- Views are fully line-covered in the parent graph, while Xdebug's top-level basic-block/outcome hits remain unobserved. These are not represented as 100% decision coverage.

## Remaining independent coverage work

No overall coverage milestone is claimed. `coverage.json` retains parent-only unknown/unexecuted `Conduit/McpNav` (the separate installed-nav report below measures a valid native context) and incomplete loaders, stub, base, bridge and cache methods. Feasible follow-up includes isolated installed-custom-nav contexts; additional Mcp save-license/pagination null/type/context variants; settings merge/legacy serialization variants; fieldtype EE2/create-page variants; persistent-cache failure paths; additional true EE2 session/URL contexts; and additional missing/malformed/type/large-input vectors where supported. Full end-to-end EE template/database behavior has not been run. The native archive load and no-PHAR regressions remain red.

## Review and publication

- Self-review completed: tests-only scope, diagnostic policy, runtime isolation, source immutability, query/service contracts and remaining measurement gaps checked.
- Independent reviewer `/root/review_priority_assets` approved exact patch `d926a0bd201f08330baa29182eafd668eff59b31ddc399536693740efd816809` on October 23, 2026. Four P2 test/harness findings plus an equals-syntax JUnit-path finding were fixed and independently verified. No actionable findings remain for this explicitly incomplete checkpoint. Reviewer independently replayed all seven complete suites, source hashes, raw coverage, PHP85 random-repeat behavior and launcher guards.
- Scoped commits:
  - `9a2c39c0135d3e9ddea4c3315461844ea9a6da72` - strict harness and pinned tooling.
  - `ae596df588a1f00b6fc9e4928aedb754825b8da1` - loose plugin/setup/view behavior.
  - `bb4e931da8357b407c0c835bc8178ea50757094e` - shipped archive/native behavior and retained regressions.
  - `4f0e6ce291481f06189802afe6114b3abc20f730` - reviewed matrix, coverage and source-blocker evidence.
  - This publication note follows the independently reviewed code commits. None is labeled a 100% coverage milestone.
- Published `4f0e6ce291481f06189802afe6114b3abc20f730` to `origin/unit-tests-2026` without force. Fresh `git ls-remote --heads origin refs/heads/unit-tests-2026` matched that SHA; local/remote left-right count was `0 0`. This note is a factual follow-up commit on the same authorized branch. Production diff against the pinned baseline is empty.

The PHAR changes include_path/autoloaders during discovery and enables process-wide file-function interception. Per-test restoration now covers request globals/include paths/new autoloaders, and a shutdown boundary restores the initial include path/autoloader stack. Class declarations and Phar::interceptFileFuncs remain process-level constraints; fresh CLI runs and native child probes contain these effects rather than claiming full reversibility.

## MCP follow-up checkpoint

The published head `325a2113552ba99794fed21bb7ba4bc9cf2d4f10` remains the independently approved 192-case checkpoint. Subsequent test-only work adds ten MCP cases: sanitized license insert/update, ignored-site keys, 50/51-character boundaries, another MSM site's existing/removed label, another add-on's key, unignore deletion, and clone-count pagination. Table-specific independent DB-result snapshots and direct seam-contract vectors distinguish site lookups without conflating license rows. Reviewer findings were addressed: negative guards detect deletes as well as inserts/updates, unignore asserts exact scoped delete ordering, and pagination observes factory total3 plus all/count on a distinct clone. That checkpoint ran 203 cases on every runtime; the current matrix above supersedes it. New PHP8.2+ errors are entirely the shipped undeclared Mcp::$data diagnostic. Source is unchanged. Current MCP source coverage improves from22/83 to73/83 lines and7/44 to31/44 decision outcomes. Remaining explicit exits after terminating EE redirects are not forced by making redirects return; genuine redirect semantics remain intact. Independent reviewer `/root/review_priority_assets` approved exact delta `0e45ff38d92a6ae6bafb596fb16da0668a868aa843990dac3241bc18943446f3` with no remaining actionable findings, independently replaying all seven complete suites, focused cases, source hashes and raw coverage. Code commit `985b83a736877d39a207535231ad8116dee2e680` was pushed to `origin/unit-tests-2026`; fresh remote SHA matched and parity was `0 0`. This evidence/status commit follows it.

## Installed-custom-nav follow-up

Seven child-process contexts now exercise an installed add-on's custom navigation extension: slash/empty aliases, headings, external URLs, buttons, direct and mapped active items, active object identity, deferred/manual generation, empty defaults and toolbar defaults/overrides. `REQ=CP` and EE7 are explicit. Each child creates a unique temporary third-party directory, symlinks unchanged shipped files, copies a test-only custom-nav fixture and removes its directory at shutdown. The extension fixture is not production source and is never counted in production coverage. The stock missing-nav native regression is unchanged and red.

That checkpoint ran210 cases per runtime; the current Facade/cache matrix above supersedes it. A separate single PHP82/Xdebug3.5.3 `nav-probe.php all` execution captures82/84 original archive-nav lines and47/54 decision outcomes, with raw outgoing data in `reports/xdebug-nav-php82.json.gz`, method details in `reports/nav-methods.tsv` and `reports/nav-coverage.json.gz`, and the skill's line-only result in `reports/nav-skill-method-lines.json.gz`. This graph stays separate from the parent graph; no external extension source or incompatible compiler/runtime/context graph is merged. Uncovered line47 is the shipped constant-false positive licensing path; line67 is the EE2 delegation path outside this EE7 context. Remaining unobserved initialization/terminal exits are retained explicitly. Native EE2 nav generation and additional malformed-extension robustness vectors remain gaps. Independent reviewer `/root/tests_low_alphabet` approved exact delta `0d4a5254fbc7a7843a004ac41db411ca54641e0ca1af7d6b2de76014f0f8bb1d` with no actionable findings after full seven-runtime replay, focused74/85 runs, real EE sidebar/header/URL/CP contract inspection, inventory regeneration and normalized raw-graph/report verification. Code commit `88c8ebb02bb9109a14bd9a02786fe901cce16240` was pushed to origin/unit-tests-2026; fresh remote SHA matched and parity was `0 0`. This evidence/status commit follows it.

## Facade and persistent-cache follow-up

This reviewed test-only delta starts at published Low Options head `2a820c9c0c14e896aafb00c15a4df1727379b9cf`. The root review identified the previous fixture's silent replacement/removal mismatch. Current EE `Legacy/Facade.php::set` rejects already-loaded services, including explicit null registrations, and `remove` unsets them. The fixture and direct harness vectors now model that used contract. Native-class vectors verify complete/idempotent removal and fresh registration. Existing-service overwrite remains a source regression; no production code changes are authorized.

The optional real-Facade probe loads `/Users/tomjaeger/Sites/ee_repo_2/system/ee/ExpressionEngine/Legacy/Facade.php` (SHA-256 `fae94c0dc34513f08fcce572fa34b7569e0ecdd3c989bc30b47f01838d301923`) and the original archive (SHA-256 `da3baba2287c04751cb25026aa64d2adf24b4b684bc62948b4d60cfb55da9774`) with declared APP_VER=7.5.0. Every required PHP runtime produces `RuntimeException: Cannot overwrite custom on the loader.` at the real Facade line90, retains the original object, and successfully removes it afterward. `reports/real-facade-probe.json` records exact commands/results. External EE source is read-only, is not included in add-on coverage, and this isolated probe does not boot the full application.

Nine persistent-cache cases add array-key retrieval, literal-hash isolation and seven typed round-trips (empty/nested arrays, an object, false/zero/string-zero/null robustness vectors). The latter preserve serialized type/value shape before and after clearing StaticCache. Array-key and literal-hash regressions are red. The new valid hit of the raw fast path improves PersistentCache from 50/70 to 51/70 lines and 24/36 to 25/36 decision outcomes; it exposes incorrect behavior rather than establishing correctness. All 27 inventory hashes are unchanged. Parent raw coverage and skill line reports are refreshed; the separate installed-nav graph was rerun after the fixture repair and is byte-identical to the published raw graph. Remaining filesystem failures, malformed serialization, legacy/runtime contexts and integration gaps remain incomplete.

Self-review checked the exact tests-only scope, real Facade guard/removal semantics, null registrations, cache key/value contracts, strict diagnostics, source hashes and report provenance. Complete seven-runtime execution now has 232 cases each, with the source-red counts above; PHP82 random/reverse order reproduces them. All 25 PHP harness/test/fixture scripts lint on PHP74 and PHP85. Independent review found an inherited database-service mismatch: the old `ee('db')` fake returned the primary builder, whereas current EE creates a fresh query via `Database::newQuery`. The revised fixture shares configured backend responses and a journal while isolating each builder's calls and data cache. Direct seam vectors verify fresh identity/live responses, and all settings-write vectors preserve a seeded primary query/cache while observing the actual site-scoped writes. Declared db/logger removal/reload vectors verify replacement object identity. Focused affected tests pass on PHP74/85 (19 cases,129 assertions). Full seven-runtime and parent coverage evidence were regenerated for the revised exact snapshot; independent reviewer `/root/review_priority_assets` approved revised exact patch `70ce304d008e27c7f44d269a9cd5c03c2e4a565cdcb5af47aea44dffcd7c4848` with no remaining actionable findings.

Fresh query builders start without a schema cache, so prior parent coverage attributed to clearing an already-filled fresh builder is no longer observed: EE3/4 each162/180 lines and94/122 outcomes, EE5 175/186 and105/130. Version remains99/100 lines with39/44 outcomes; its already-absent primary table-list branch remains a behavior gap. These honest decreases are retained rather than filling a new builder with artificial cache state.

Published reviewed Facade/cache commits `55210a2` (service/query fidelity), `975a041` (cache values/keys), and `b12e4b3c606db76e1febf13821058ab4fa311b8f` (evidence) to `origin/unit-tests-2026` without force. Their combined binary diff from `2a820c9` exactly matches the approved SHA-256 above. A fresh remote branch lookup matched `b12e4b3c606db76e1febf13821058ab4fa311b8f`, with local/remote parity `0 0` and a clean checkout. The reviewer independently replayed all seven complete suites, focused74/85 tests, actual-Facade probes, normalized raw/report metrics, tool/source hashes and launcher zero-selection guard. This factual status note follows that reviewed publication. Remaining coverage/integration constraints are explicitly incomplete; no production fixes were made.
