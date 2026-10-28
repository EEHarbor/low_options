# Practical test review — 2026-10-28

This update keeps tests focused on ordinary supported add-on behavior. Dedicated PHAR/Foundation internal contracts belong in the Foundation repository. Production code and bundled dependencies are unchanged.

- Remove the retained Foundation constructor contract.

The complete retained suites ran under all seven PHP versions with strict diagnostics. Errors and failures remain visible for the current source; these tests do not certify compatibility or a green suite. Earlier campaign measurements are historical and were not recomputed for this review.

| PHP | Suite | Tests | Assertions | Errors | Failures |
| --- | --- | ---: | ---: | ---: | ---: |
| 7.4 | main | 27 | 75 | 0 | 3 |
| 8.0 | main | 27 | 77 | 0 | 1 |
| 8.1 | main | 27 | 77 | 0 | 1 |
| 8.2 | main | 27 | 77 | 0 | 1 |
| 8.3 | main | 27 | 77 | 0 | 1 |
| 8.4 | main | 27 | 77 | 0 | 1 |
| 8.5 | main | 27 | 77 | 0 | 1 |

Test code revision: `e3f9e95e8082e61dbcc9cda47a4d627391fcd1af`. Validation uses isolated EE collaborators and does not replace an installed ExpressionEngine/database/browser integration run.
