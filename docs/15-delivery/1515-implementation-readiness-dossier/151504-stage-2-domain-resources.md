---
title: "Stage 2 — Domain & Resources"
description: "| Gate | Evidence | Check | Status |"
type: delivery
tags: \[delivery, implementation-readiness-dossier, stage, "2"]
updated: 2026-07-30
---

# Stage 2 — Domain & Resources

**Risk order:** 2
**Decision reference:** ADR 100304, ADR 100311, ADR 100313, ADR 100314
**Status:** complete

## Acceptance gates

| Gate                                                                               | Evidence                                                                   | Check                                             | Status |
| ---------------------------------------------------------------------------------- | -------------------------------------------------------------------------- | ------------------------------------------------- | ------ |
| UUIDv7 trait verification (HasUuids on all models)                                 | `tests/Architecture/ArchitectureTest.php`                                  | `composer test:arch`                              | Pass   |
| Source Identity Registry (public.source_identities uniqueness and JSONB key)       | `database/migrations/0001_01_01_000001_create_source_identities_table.php` | `php artisan test --filter=SourceIdentit`         | Pass   |
| Shadow schema import pipeline (ChinookImporter, NorthwindImporter, PagilaImporter) | `app/Services/ProductImport/{Chinook,Northwind,Pagila}Importer.php`        | `php artisan test --filter=ProductImportPipeline` | Pass   |

## Automated checks

- `composer test:arch`
- `php artisan test --testsuite=Feature`

## Operator commands

```bash
composer test:arch
php artisan test --testsuite=Feature --filter=Import
php artisan test --testsuite=Feature --filter=SourceIdentit
```

## Evidence location

- `tests/Feature/Import/ProductImportPipelineTest.php`

## Recovery procedure

1. Run `composer test:arch` to confirm the architecture rules still hold; address any violation before proceeding.
2. Re-run `php artisan test --testsuite=Feature --filter=Import` to verify the import pipeline still loads each shadow schema.
3. If `source_identities` uniqueness regresses, re-run seeding and confirm the JSONB key constraint via the migration.
