# Low Options practical tests

Current review corrections and PHP 7.4–8.5 results: [PRACTICAL_REVIEW_2026.md](PRACTICAL_REVIEW_2026.md). Earlier campaign counts are historical.

The configured suite covers field lookup, flat/grouped options, include/exclude parameters, existing values, sorting, template query filters, missing/empty results, setup metadata and the loose add-on license view. Normal add-on inheritance and autoloading use the shipped PHAR. Tests whose subject was bundled Foundation/PHAR implementation, archive loading or synthetic harness protocols have been removed; the Foundation repository owns shared-code tests.

Run the pinned PHPUnit 9.6.38 runner after `tests/.phpunit/install.sh`:

```sh
'/Users/tomjaeger/Library/Application Support/Herd/bin/php83' -d error_reporting=-1 -d display_errors=stderr -d display_startup_errors=1 tests/.phpunit/phpunit-9.phar -c phpunit.xml --no-coverage
```

Complete retained suite verified on PHP 7.4 and 8.0 through 8.5 on 2026-10-27 with strict diagnostics. PHP 7.4: 28 tests, 79 assertions, 3 failures. PHP 8.0-8.5: 28 tests, 81 assertions, 1 failure each. No errors, warnings or skips. Real zero-valued option losses remain failing.

This tests-only cleanup does not change production code. Earlier status and coverage records describe the historical broader suite; they are preserved as history.
