# Low Options Foundation build

The local builder follows the existing Foundation substitution and PHAR packaging process from `tools` commit `11a2ebabc92280b424d7aac76e78ac689eb29df7`. It changes only this add-on's artifact. It does not execute the broad shared gulp task.

All 22 original members matched those templates after the recorded namespace, module, Foundation version, license constant and exact shipped validation JavaScript substitutions. The shipped `version_pro` StaticCache alias is retained. Original hashes, modes, compression and metadata are recorded in `foundation/provenance.json`.

Build with an explicit PHP binary and per-process PHAR write permission:

```sh
'/Users/tomjaeger/Library/Application Support/Herd/bin/php85' \
  -d phar.readonly=0 -d error_reporting=-1 -d display_errors=stderr \
  -d display_startup_errors=1 build/foundation.php
```

The archive retains its original SHA-1 signature algorithm, `low_options.phar` alias, executable/read modes, uncompressed members, null metadata and exact licensing JavaScript. Thirteen original PHP members and the extraction stub contain scoped fixes; nine original members remain byte-identical. One `SerializedValue` helper shares native parse handling between field settings and persistent cache. It preserves native object types and references and forwards diagnostics from callbacks. Native callback error-handler stack/mask preservation remains a confirmed unresolved blocker; this best candidate is not approved for publication. Its length-aware legacy escaped-string rewriting was adapted from the reviewed Fieldpack decoder, with signed object/custom payload counts retained after native PHP7.4 contract probes. Native parsing still decides value validity. Extraction rejects scoped root/container links and unlinks direct helper links without following their targets. The builder recalculates the default stub length and verifies every transformed member before replacing the local artifact. Normal PHAR build timestamps may change.

Configured tests execute the distributed archive. Historical source inventories and coverage files describe their recorded baseline; current compatibility evidence is documented separately in `PHP_COMPATIBILITY_2026.md`.

The current ordinary correction keeps original nested reporting scopes and native operand ownership. Nonstring operands are transferred to real native parser conversion; callback-hidden legacy tokens and prefixes remain an explicit failing compatibility boundary. First caller binding uses uninitialized typed state at each original lexical reader, including actual pinned PHPUnit restoration and PHP74 inheritance. Original replacement parameters remain alive through settings database updates. These changes do not resolve the native handler stack/severity-mask blocker.


The renewed ordinary batch preserves both public setter parameters through serialization and database completion, first native callee binding through actual pinned PHPUnit static restoration, and temporary operand release on success and exceptional unwinding. Each original cache-directory creation site now retains independent first resolution, original arguments and its original reporting scope. A legal hook installed during generic-directory creation can govern the later module-directory site. NAN settings roots retain their original PHP 7.4 value without PHP 8.5 boolean coercion. Permanent positive contracts use actual distributed source and an unchanged EE file helper.

Nonstring parser arguments remain delegated to native conversion in its original frame and reporting scope. Legacy escaped-string and trailing-prefix bytes hidden inside those callbacks remain positive failing compatibility cases on affected newer runtimes; no diagnostic expectation or exclusion makes them green. Temporary string-parser and native-mkdir wrappers still cannot preserve arbitrary prior handler severity masks and callback-installed handler stacks (B1). The exact final matrix and original/current counterexamples are retained in the replacement blocker packet; this candidate is not publication-ready. The ordinary batch was authored05; the real EE helper is copied unchanged with recorded provenance. Historical lexer/packaging provenance remains as previously disclosed.

Native directory creation keeps the original opportunity to create a stream directory even when its parent cannot be statted. The caller checks the resulting directory after native creation; exact native filesystem failures retain their existing local policy. An actual stream regression exercises both cache-directory creations and the unchanged EE file writer.
