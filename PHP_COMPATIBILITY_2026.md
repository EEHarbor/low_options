# Add-on PHP compatibility scope

Foundation changes are excluded from this candidate. The required distributed PHAR is restored byte-for-byte to original tests baseline `efc2b3bbff5bcad75c9530c55225da99e9ed3a02`, which has the same production bytes as locally held develop `ffa06e5eed4ea35657d3175a00fc82a297f8b912`. The original generated autoloader remains unchanged. No shared library is rebuilt, replaced, patched or moved into a loose add-on helper.

The retained add-on source fixes cover: Zero-valued option labels, numeric-key inclusion/exclusion matching and NULL optional parameter handling.

When `show_empty=no`, flat and grouped numeric option keys use the same string-key comparison as the existing ignore parser. A stored `'0'` keeps option `0 => '0'`; an unrelated stored value removes it on every supported PHP runtime. This avoids PHP7.4's numeric-zero comparison with unrelated text while preserving numeric/string key matching.

All original configured suites, providers and tests remain selected. Only campaign-added Foundation-only tests/probes/build sources and Foundation-specific increments in original test files are removed. The original test files and history remain. Historical pre-cleanup matrices, published checkpoints, native faults and source snapshots are preserved under the campaign evidence and Git history; they do not validate this candidate.

Run the complete configured suite with an explicit Herd binary:

```sh
PHP_BINARY='/Users/tomjaeger/Library/Application Support/Herd/bin/php74' ./tests/.phpunit/run-phpunit.sh -c phpunit.xml --do-not-cache-result
```

Repeat for php80, php81, php82, php83, php84 and php85. The checked runner retains strict startup settings, prethrow diagnostics, complete process streams and actual native outcomes. Caught or ignored diagnostics remain rejection conditions. Generic authority controls use the add-on metadata entry point rather than relying on a changed Foundation cache. Deliberate authority faults are tool controls, never clean product compatibility credit.

The retained original Foundation tests can fail against the restored dependency. Those failures must remain visible in the complete matrix and are shared dependency failures; they do not authorize test skips, diagnostic filters, expected-warning product assertions or new Foundation fixes. No complete compatibility or publication approval is claimed. The scope-correction review packet records the exact cleanup tree and final seven-version results. No live EE installation/database/browser or coverage-completion claim is made.
