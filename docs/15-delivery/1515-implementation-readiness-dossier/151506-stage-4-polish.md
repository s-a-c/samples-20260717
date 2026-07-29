# Stage 4 — Polish

**Risk order:** 4
**Decision reference:** ADR 100326, ADR 100331
**Status:** complete

## Acceptance gates

| Gate                                                                         | Evidence                                             | Check                                               | Status |
| ---------------------------------------------------------------------------- | ---------------------------------------------------- | --------------------------------------------------- | ------ |
| PHPStan level: max baseline citation guard (PhpStanBaselineCitationTest.php) | `tests/Architecture/PhpStanBaselineCitationTest.php` | `php artisan test --filter=PhpStanBaselineCitation` | Pass   |
| 26 Architecture rules (ArchitectureTest.php)                                 | `tests/Architecture/ArchitectureTest.php`            | `composer test:arch`                                | Pass   |
| CI Quality Gate workflow (.github/workflows/tests.yml)                       | `.github/workflows/tests.yml`                        | `GitHub Actions tests.yml green`                    | Pass   |

## Automated checks

- `composer test:unit`
- `composer test:type-cov`

## Operator commands

```bash
composer test --filter=PhpStanBaselineCitation
composer test:arch
git diff --exit-code .github/workflows/tests.yml
```

## Evidence location

- `.github/workflows/tests.yml`
- `tests/Architecture/ArchitectureTest.php`

## Recovery procedure

1. Run `composer test:arch` to confirm all architecture rules pass; cite or resolve any new baseline entry.
2. Re-run the PHPStan baseline citation guard (`composer test --filter=PhpStanBaselineCitation`) so every ignored error remains justified.
3. If the CI Quality Gate workflow regresses, re-run `.github/workflows/tests.yml` locally via `act` or push a fix branch until CI is green.
