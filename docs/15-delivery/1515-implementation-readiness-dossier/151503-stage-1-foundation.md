# Stage 1 — Foundation

**Risk order:** 1
**Decision reference:** ADR 100302, ADR 100328, ADR 100332
**Status:** complete

## Acceptance gates

| Gate                                | Evidence                                                               | Check                                          | Status |
| ----------------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------- | ------ |
| PostgreSQL extensions DDL migration | `database/migrations/0001_01_01_000000_create_postgres_extensions.php` | `php artisan migrate:fresh`                    | Pass   |
| Postgres extensions health test     | `tests/Feature/Postgres/PostgresExtensionsTest.php`                    | `php artisan test --filter=PostgresExtensions` | Pass   |
| pgsql:check artisan command         | `app/Console/Commands/PgsqlCheck.php`                                  | `php artisan pgsql:check`                      | Pass   |

## Automated checks

- `composer types:check`
- `composer test:coverage`

## Operator commands

```bash
php artisan pgsql:check
php artisan test --filter=PostgresExtensions
composer types:check
```

## Evidence location

- `.github/workflows/tests.yml`
- `tests/Feature/Postgres/PostgresExtensionsTest.php`

## Recovery procedure

1. Re-run `php artisan migrate:fresh --seed` to restore the PostgreSQL extension DDL and base schema.
2. If `php artisan pgsql:check` reports a missing extension, install it at the PostgreSQL server level, then re-run the check and the Pest suite.
