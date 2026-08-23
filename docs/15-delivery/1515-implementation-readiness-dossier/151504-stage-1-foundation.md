---
title: Stage 1 — Foundation
description: Acceptance Stage 1 (Foundation) of the Implementation-Readiness Dossier — gates, checks, operator commands, evidence, and recovery.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [dossier, delivery, acceptance]
created: 2026-08-23
updated: 2026-08-23
---

# Stage 1 — Foundation

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Acceptance gates](#1-acceptance-gates)
- [2. Automated checks](#2-automated-checks)
- [3. Operator commands](#3-operator-commands)
- [4. Evidence location](#4-evidence-location)
- [5. Recovery procedure](#5-recovery-procedure)

</details>

---

**Risk order:** 1
**Decision reference:** ADR 100302, ADR 100328, ADR 100332
**Status:** complete

## 1. Acceptance gates

| Gate                                | Evidence                                                               | Check                                          | Status |
| ----------------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------- | ------ |
| PostgreSQL extensions DDL migration | `database/migrations/0001_01_01_000000_create_postgres_extensions.php` | `php artisan migrate:fresh`                    | Pass   |
| Postgres extensions health test     | `tests/Feature/Postgres/PostgresExtensionsTest.php`                    | `php artisan test --filter=PostgresExtensions` | Pass   |
| pgsql:check artisan command         | `app/Console/Commands/PgsqlCheck.php`                                  | `php artisan pgsql:check`                      | Pass   |

## 2. Automated checks

- `composer types:check`
- `composer test:coverage`

## 3. Operator commands

```bash
php artisan pgsql:check
php artisan test --filter=PostgresExtensions
composer types:check
```

## 4. Evidence location

- `.github/workflows/tests.yml`
- `tests/Feature/Postgres/PostgresExtensionsTest.php`

## 5. Recovery procedure

1. Re-run `php artisan migrate:fresh --seed` to restore the PostgreSQL extension DDL and base schema.
2. If `php artisan pgsql:check` reports a missing extension, install it at the PostgreSQL server level, then re-run the check and the Pest suite.
