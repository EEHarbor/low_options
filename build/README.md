# Low Options Foundation build

The local builder follows the existing Foundation substitution and PHAR packaging process from `tools` commit `11a2ebabc92280b424d7aac76e78ac689eb29df7`. It changes only this add-on's artifact. It does not execute the broad shared gulp task.

All 22 original members matched those templates after the recorded namespace, module, Foundation version, license constant and exact shipped validation JavaScript substitutions. The shipped `version_pro` StaticCache alias is retained. Original hashes, modes, compression and metadata are recorded in `foundation/provenance.json`.

Build with an explicit PHP binary and per-process PHAR write permission:

```sh
'/Users/tomjaeger/Library/Application Support/Herd/bin/php85' \
  -d phar.readonly=0 -d error_reporting=-1 -d display_errors=stderr \
  -d display_startup_errors=1 build/foundation.php
```

The archive retains its original SHA-1 signature algorithm, `low_options.phar` alias, executable/read modes, uncompressed members, null metadata and exact licensing JavaScript. Thirteen original PHP members and the extraction stub contain scoped fixes; nine original members remain byte-identical. One small `SerializedValue` helper shares native parse handling between field settings and persistent cache. It preserves native object types and references and forwards diagnostics from callbacks. The builder recalculates the default stub length and verifies every transformed member before replacing the local artifact. Normal PHAR build timestamps may change.

Configured tests execute the distributed archive. Historical source inventories and coverage files describe their recorded baseline; current compatibility evidence is documented separately in `PHP_COMPATIBILITY_2026.md`.
