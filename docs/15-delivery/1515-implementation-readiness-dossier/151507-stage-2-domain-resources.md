---
title: Stage 2 — Domain & Resources
description: Acceptance Stage 2 (Domain & Resources) of the Implementation-Readiness Dossier — gates, checks, operator commands, evidence, and recovery.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [dossier, delivery, acceptance]
created: 2026-08-23
updated: 2026-08-23
---

# Stage 2 — Domain & Resources

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

**Risk order:** 2
**Decision reference:** ADR 100304, ADR 100311, ADR 100313, ADR 100314
**Status:** complete

## 1. Acceptance gates

| Gate                                                                               | Evidence                                                                   | Check                                             | Status |
| ---------------------------------------------------------------------------------- | -------------------------------------------------------------------------- | ------------------------------------------------- | ------ |
| UUIDv7 trait verification (HasUuids on all models)                                 | `tests/Architecture/ArchitectureTest.php`                                  | `composer test:arch`                              | Pass   |
| Source Identity Registry (public.source_identities uniqueness and JSONB key)       | `database/migrations/0001_01_01_000001_create_source_identities_table.php` | `php artisan test --filter=SourceIdentit`         | Pass   |
| Shadow schema import pipeline (ChinookImporter, NorthwindImporter, PagilaImporter) | `app/Services/ProductImport/{Chinook,Northwind,Pagila}Importer.php`        | `php artisan test --filter=ProductImportPipeline` | Pass   |

## 2. Automated checks

- `composer test:arch`
- `php artisan test --testsuite=Feature`

## 3. Operator commands

```bash
composer test:arch
php artisan test --testsuite=Feature --filter=Import
php artisan test --testsuite=Feature --filter=SourceIdentit
```

## 4. Evidence location

- `tests/Feature/Import/ProductImportPipelineTest.php`

## 5. Recovery procedure

1. Run `composer test:arch` to confirm the architecture rules still hold; address any violation before proceeding.
2. Re-run `php artisan test --testsuite=Feature --filter=Import` to verify the import pipeline still loads each shadow schema.
3. If `source_identities` uniqueness regresses, re-run seeding and confirm the JSONB key constraint via the migration.
