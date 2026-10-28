# Coverage snapshot

Basic-block execution is distinct from decision outcomes. Raw outgoing data and synthetic terminal limitations are retained in coverage.json.gz and xdebug-php82.json.gz. Unexecuted source is unknown. This is an incomplete checkpoint.

| Source unit | Lines | Basic blocks | Decision outcomes |
| --- | ---: | ---: | ---: |
| low_options/addon.setup.php | 9/9 | 0/1 | 0/0 |
| low_options/autoload.php | 23/29 | 23/31 | 13/26 |
| low_options/low_options.phar/<stub>.php | 55/154 | 30/109 | 24/104 |
| low_options/low_options.phar/FluxCapacitor/Base/Ext.php | 59/61 | 31/33 | 13/18 |
| low_options/low_options.phar/FluxCapacitor/Base/Ft.php | 42/56 | 25/44 | 15/34 |
| low_options/low_options.phar/FluxCapacitor/Base/Mcp.php | 73/83 | 43/50 | 31/44 |
| low_options/low_options.phar/FluxCapacitor/Base/Mod.php | 10/10 | 6/7 | 4/4 |
| low_options/low_options.phar/FluxCapacitor/Base/Pi.php | 5/10 | 2/3 | 0/0 |
| low_options/low_options.phar/FluxCapacitor/Base/Tab.php | 22/24 | 14/16 | 6/8 |
| low_options/low_options.phar/FluxCapacitor/Base/Upd.php | 25/44 | 13/20 | 7/13 |
| low_options/low_options.phar/FluxCapacitor/Bridge/AbstractBridge.php | 13/13 | 11/12 | 2/2 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE.php | 12/30 | 1/18 | 0/14 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE2.php | 152/195 | 117/167 | 79/147 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE3.php | 162/180 | 130/149 | 94/122 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE4.php | 162/180 | 130/149 | 94/122 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE5.php | 175/186 | 146/159 | 105/130 |
| low_options/low_options.phar/FluxCapacitor/Bridge/EE6.php | 2/2 | 0/1 | 0/0 |
| low_options/low_options.phar/FluxCapacitor/Conduit/GlobalFieldSettings.php | 23/23 | 12/13 | 6/6 |
| low_options/low_options.phar/FluxCapacitor/Conduit/McpNav.php | 0/0 | 0/0 | 0/0 |
| low_options/low_options.phar/FluxCapacitor/Conduit/PersistentCache.php | 64/70 | 42/45 | 33/36 |
| low_options/low_options.phar/FluxCapacitor/Conduit/StaticCache.php | 18/18 | 15/16 | 6/6 |
| low_options/low_options.phar/FluxCapacitor/Conduit/Version.php | 66/100 | 39/60 | 27/44 |
| low_options/low_options.phar/FluxCapacitor/FluxCapacitor.php | 5/5 | 1/2 | 0/0 |
| low_options/low_options.phar/Views/license.php | 14/14 | 0/6 | 0/4 |
| low_options/low_options.phar/index.php | 7/13 | 2/6 | 1/4 |
| low_options/pi.low_options.php | 134/135 | 115/118 | 97/106 |
| low_options/views/license.php | 14/14 | 0/6 | 0/4 |

Installed-custom-nav context (separate single PHP82 process, not merged): Conduit/McpNav.php reaches82/84 lines,67/69 basic blocks and47/54 decision outcomes. See nav-coverage.json.gz and xdebug-nav-php82.json.gz. The extension fixture is not add-on source and is excluded from instrumentation. Stock missing-nav diagnostics remain red.

Crypto runs in six independent PHP82 processes. Their complete original raw JSON bytes and SHA256 values are retained in xdebug-crypto-php82.json.gz; crypto-coverage.json.gz retains all per-unit/per-method reports separately. These context graphs are not merged with each other or with the parent. The parent decreases after moving actual constructor/check/key operations into native processes.

| Crypto context | Version::check lines / outcomes | Upd constructor lines / outcomes | Upd::install lines / outcomes |
| --- | ---: | ---: | ---: |
| payload-absent | 29/34 / 7/16 | unknown | unknown |
| payload-installed | 29/34 / 7/16 | unknown | unknown |
| public-key | 0/34 / 0/16 | unknown | unknown |
| updater | 28/33 / 7/16 | 6/6 / 0/0 | 9/10 / 1/2 |
| version-development | 32/34 / 10/16 | unknown | unknown |
| version | 32/34 / 10/16 | unknown | unknown |

Stub helpers execute the original loaded Extract_low_options class. _unpack reaches20/20 lines,4/4 basic blocks and2/2 decision outcomes; extractFile15/18 lines,12/14 blocks and10/12 outcomes; Unix tmpdir4/9 lines,4/14 blocks and3/12 outcomes; _removeTmpFiles9/10 lines,10/12 blocks and9/12 outcomes. Windows-only temporary selection remains outside the macOS matrix. Corrupt size/checksum termination now has three separate native contexts below; they do not alter this parent measurement. Strict @rmdir on a nonempty child directory prevents the later recursive cleanup call from executing. Empty/files/empty-subdirectory controls pass. No source completeness claim is made.
The six crypto graphs were freshly collected after sharing their runner. The installed-nav graph is unchanged from its independently approved checkpoint and is not claimed rerun. Every context remains separate from this fresh parent run.

The deterministic dangling-link robustness vector covers the file_exists(false) cleanup exit. It leaves the link and reaches a strict final rmdir diagnostic; its owned-link cleanup remains in test finally. Incompressible SHA256-derived binary fixtures ensure encoded gzip/bzip2 data also reaches the full read chunk.

Three newly retained native terminal contexts each execute original extractFile without merging graphs. Valid11/18 lines,9/14 blocks,7/12 decision outcomes; checksum11/18,9/14,7/12; size11/18,8/14,6/12. All retain two unobserved synthetic terminals. xdebug-stub-php82.json.gz preserves full raw JSON/out/out_hit and source/runtime/selected-suite provenance; stub-coverage.json.gz records every unit/method separately. The size-error message remains source-red because it references undefined $stat.

The final312-case checkpoint leaves the parent metrics above unchanged. All six crypto, three terminal and installed-nav contexts were freshly rerun after the fixture/runner updates. Forty additional legacy graphs (20 each PHP74 and82) retain full input/options and source/runtime/hash provenance independently in xdebug-legacy-php74/82.json.gz and legacy-php74/82-coverage.json.gz. Positive EE2 view contexts reach5/5 lines and5/5 basic blocks, with2/4 ordinary outcomes per individual graph; their outcomes are not merged. URL/session, page/offset and view contexts do not boot historical EE. Missing source/outcomes and synthetic exit limits remain explicit in all detailed reports.
