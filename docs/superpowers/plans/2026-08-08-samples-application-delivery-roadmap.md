---
title: "Samples Application Delivery Roadmap Implementation Plan"
description: "Canonical project-wide roadmap delivery plan consolidating prior wayfinder decisions, completed work, and remaining delivery gaps."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [plan, roadmap, implementation, wayfinder, delivery, samples]
created: 2026-08-08
updated: 2026-08-31
---

# Samples Application Delivery Roadmap Implementation Plan

> [!IMPORTANT] **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver and accept the Laravel Samples application end to end: three independently importable/resettable/searchable sample products, correct Admin workflows, lifecycle-safe derived data, and evidence-backed Herd + Linux acceptance.

**Architecture:** Preserve the accepted PostgreSQL multi-schema shadow-swap architecture. Upstream data is loaded into `<product>_source`, transformed through Eloquent staging models into migration-built `<product>_staging`, validated, atomically published as `<product>`, and followed by explicit recreation of `public.product_portfolio_snapshots`, search rebuild, embedding drain, and Reset Evidence persistence. The roadmap treats every derived object and operator surface as a lifecycle invariant, not just a fresh-migration artifact.

**Tech Stack:** PHP 8.5, Laravel 13.24, Livewire 4, Filament 5, Pest 5, PostgreSQL 18 with `pgvector`, `laravel/ai`, Spatie Permission/Activitylog, GitHub Actions, Laravel Herd, Composer, pnpm/Vite+.

---

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Global Constraints](#1-global-constraints)
- [2. Current-State Ledger](#2-current-state-ledger)
    - [2.1. Completed Maps](#21-completed-maps)
    - [2.2. Completed Original Decisions: Issues 2–13](#22-completed-original-decisions-issues-213)
    - [2.3. Completed Refined Decisions: Issues 16–42](#23-completed-refined-decisions-issues-1642)
    - [2.4. Completed Remediation: Issues 46–63](#24-completed-remediation-issues-4663)
    - [2.5. Completed Admin Decisions: Issues 65–69](#25-completed-admin-decisions-issues-6569)
    - [2.6. Completed Pest/CI Work: Issues 74–80](#26-completed-pestci-work-issues-7480)
    - [2.7. Existing Open Work](#27-existing-open-work)
- [3. File and Responsibility Map](#3-file-and-responsibility-map)
    - [3.1. Existing files to modify](#31-existing-files-to-modify)
    - [3.2. New files to create](#32-new-files-to-create)
- [4. Phase 0 — Baseline and Governance](#4-phase-0--baseline-and-governance)
    - [4.1. Task 0: Audit roadmap status and acceptance evidence](#41-task-0-audit-roadmap-status-and-acceptance-evidence)
- [5. Phase 1 — Runtime and Schema Safety](#5-phase-1--runtime-and-schema-safety)
    - [5.1. Task 1: Restore portfolio view after shadow-schema publish](#51-task-1-restore-portfolio-view-after-shadow-schema-publish)
    - [5.2. Task 2: Build migration-backed staging and schema-safe projections](#52-task-2-build-migration-backed-staging-and-schema-safe-projections)
- [6. Phase 2 — Import and Reset Correctness](#6-phase-2--import-and-reset-correctness)
    - [6.1. Task 3: Implement Eloquent staging transform infrastructure](#61-task-3-implement-eloquent-staging-transform-infrastructure)
    - [6.2. Task 4: Complete Chinook import mapping and acceptance](#62-task-4-complete-chinook-import-mapping-and-acceptance)
    - [6.3. Task 5: Complete Northwind import mapping and resources](#63-task-5-complete-northwind-import-mapping-and-resources)
    - [6.4. Task 6: Complete Pagila import mapping and normalization](#64-task-6-complete-pagila-import-mapping-and-normalization)
    - [6.5. Task 7: Complete reset evidence, invariants, embedding drain, and recovery](#65-task-7-complete-reset-evidence-invariants-embedding-drain-and-recovery)
- [7. Phase 3 — Product Panels and Admin Workflows](#7-phase-3--product-panels-and-admin-workflows)
    - [7.1. Task 8: Verify Admin import and stats lifecycle](#71-task-8-verify-admin-import-and-stats-lifecycle)
- [8. Phase 4 — Search and Derived Read Models](#8-phase-4--search-and-derived-read-models)
    - [8.1. Task 9: Complete search projections and Golden Search Corpus](#81-task-9-complete-search-projections-and-golden-search-corpus)
- [9. Phase 5 — Quality, CI, and Documentation](#9-phase-5--quality-ci-and-documentation)
    - [9.1. Task 10: Reconcile ADRs, dossier, CI, and project documentation](#91-task-10-reconcile-adrs-dossier-ci-and-project-documentation)
- [10. Phase 6 — Release Acceptance and Operations](#10-phase-6--release-acceptance-and-operations)
    - [10.1. Task 11: Run two-environment release acceptance and sign-off](#101-task-11-run-two-environment-release-acceptance-and-sign-off)
- [11. Phase 7 — Closure and Stabilization](#11-phase-7--closure-and-stabilization)
    - [11.1. Task 12: Teams and Settings Livewire verification — **verified locally**](#111-task-12-teams-and-settings-livewire-verification--verified-locally)
    - [11.2. Task 13: Admin ProductCardActions verification — **verified locally**](#112-task-13-admin-productcardactions-verification--verified-locally)
    - [11.3. Task 14: Real source-data imports through the production pipeline](#113-task-14-real-source-data-imports-through-the-production-pipeline)
    - [11.4. Task 15: Linux CI with pgvector/pgvector:pg18](#114-task-15-linux-ci-with-pgvectorpgvectorpg18)
    - [11.5. Task 17: PHPStan quality gate — **verified locally**](#115-task-17-phpstan-quality-gate--verified-locally)
    - [11.6. Task 18: Coverage gate remediation — **verified**](#116-task-18-coverage-gate-remediation--verified)
    - [11.7. Task 16: Documentation and acceptance-record alignment](#117-task-16-documentation-and-acceptance-record-alignment)
- [12. Historical Task Reconciliation Rules](#12-historical-task-reconciliation-rules)
- [13. Self-Review](#13-self-review)
    - [13.1. Scope coverage](#131-scope-coverage)
    - [13.2. Placeholder scan](#132-placeholder-scan)
    - [13.3. Dependency consistency](#133-dependency-consistency)
- [14. Execution Handoff](#14-execution-handoff)

---

</details>

## 1. Global Constraints

- Use the accepted PostgreSQL-only suite; `phpunit.xml` points all tests at `pgsql` and CI uses `pgvector/pgvector:pg18`.
- Keep product schemas isolated: `chinook`, `northwind`, and `pagila`; never merge their business domains.
- Keep the shadow-swap decision from ADR 100308; do not replace it with truncate-and-reload.
- Eloquent is the domain write path; staging model subclasses are the explicit import exemption from `BelongsToProductDomain`.
- Use `SourceIdentityRegistry` for stable source-key to UUIDv7 mapping and all transformed foreign keys.
- Suppress `Tier1SourceObserver` during staging and drain embeddings after publish.
- Recreate `public.product_portfolio_snapshots` after every successful schema publish because `DROP SCHEMA ... CASCADE` removes dependent public views.
- All methods have explicit parameter and return types; use PHP 8.5 constructor property promotion and curly braces.
- Use `mb_*` functions for text processing and PHPDoc array shapes for structured data.
- Use Pest 5 syntax and the existing `tests/Pest.php` conventions.
- Create PHP classes/tests with Artisan generators where applicable; do not add dependencies without approval.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Run focused Pest tests before broader suites, then `composer phpstan:analyze`, `composer test:arch`, and the relevant CI gate.
- No issue is accepted solely because it is tracker-closed. Record `tracker-closed`, `implemented`, `verified`, and `accepted` separately.
- GitHub map: [Wayfinder — Samples Application Delivery Roadmap](https://github.com/s-a-c/samples-20260717/issues/85).
- Beads parent: `samples-20260717-7rg`.

---

## 2. Current-State Ledger

The following work is part of this roadmap even when its original issue is closed. These entries are historical prerequisites and must be linked from evidence; they are not recreated as duplicate issues.

### 2.1. Completed Maps

| Tracker item                                                                                                       | Status entering this roadmap            | Roadmap treatment                                                            |
| ------------------------------------------------------------------------------------------------------------------ | --------------------------------------- | ---------------------------------------------------------------------------- |
| [Wayfinder — Laravel Sample Database Products](https://github.com/s-a-c/samples-20260717/issues/1)                 | Tracker-closed decision map             | Historical architecture baseline                                             |
| [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)                          | Tracker-closed decision map             | Historical refined architecture baseline                                     |
| [Wayfinder #15 — Gap Remediation](https://github.com/s-a-c/samples-20260717/issues/49)                             | Tracker-closed execution map            | Historical remediation inventory; evidence still reconciled by Task 0        |
| [Wayfinder — Admin UI Product Import & Stats Refresh Buttons](https://github.com/s-a-c/samples-20260717/issues/64) | Open decision map with closed decisions | Existing Admin decisions feed Task 6; execution is not accepted until Task 6 |
| [Wayfinder — Pest 5 Comprehensive Adoption](https://github.com/s-a-c/samples-20260717/issues/73)                   | Open map with closed execution tickets  | Historical CI work; release evidence is rechecked by Task 0 and Task 9       |

### 2.2. Completed Original Decisions: Issues 2–13

| Issue                                                     | Completed task                                                                               |
| --------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| [2](https://github.com/s-a-c/samples-20260717/issues/2)   | Verify Laravel 13, Livewire SFC, and Filament 5 integration boundaries                       |
| [3](https://github.com/s-a-c/samples-20260717/issues/3)   | Select the SQLite text and vector extension stack; later superseded by the PostgreSQL pivot  |
| [4](https://github.com/s-a-c/samples-20260717/issues/4)   | Audit and pin the three upstream sample datasets                                             |
| [5](https://github.com/s-a-c/samples-20260717/issues/5)   | Define application, domain, panel, and resource namespace architecture                       |
| [6](https://github.com/s-a-c/samples-20260717/issues/6)   | Define transformed schemas and UUIDv7 identity mapping                                       |
| [7](https://github.com/s-a-c/samples-20260717/issues/7)   | Define deterministic import and independent reset semantics                                  |
| [8](https://github.com/s-a-c/samples-20260717/issues/8)   | Define hybrid search, embeddings, and index synchronization                                  |
| [9](https://github.com/s-a-c/samples-20260717/issues/9)   | Define team authorization and product entitlement semantics                                  |
| [10](https://github.com/s-a-c/samples-20260717/issues/10) | Define cross-product reporting and overall-panel read models                                 |
| [11](https://github.com/s-a-c/samples-20260717/issues/11) | Define verification, delivery, and operational acceptance                                    |
| [12](https://github.com/s-a-c/samples-20260717/issues/12) | Define native SQLite extension bootstrap and diagnostics; superseded by the PostgreSQL pivot |
| [13](https://github.com/s-a-c/samples-20260717/issues/13) | Define authorization, audit, and configurable-dashboard package boundaries                   |

### 2.3. Completed Refined Decisions: Issues 16–42

| Issue                                                     | Completed task                                                                |
| --------------------------------------------------------- | ----------------------------------------------------------------------------- |
| [16](https://github.com/s-a-c/samples-20260717/issues/16) | Select Filament panel install order and discovery roots                       |
| [17](https://github.com/s-a-c/samples-20260717/issues/17) | Decide Pest test pyramid, coverage, architecture rules, and acceptance layers |
| [18](https://github.com/s-a-c/samples-20260717/issues/18) | Lock Larastan max-level and no-baseline policy                                |
| [19](https://github.com/s-a-c/samples-20260717/issues/19) | Verify Filament 5, Shield, Livewire teams, and Fortify coexistence            |
| [20](https://github.com/s-a-c/samples-20260717/issues/20) | Verify sqlite-vec asset availability; superseded by PostgreSQL pivot          |
| [21](https://github.com/s-a-c/samples-20260717/issues/21) | Decide extension connection gate; superseded by PostgreSQL pivot              |
| [22](https://github.com/s-a-c/samples-20260717/issues/22) | Decide vector capability probe; superseded by PostgreSQL pivot                |
| [23](https://github.com/s-a-c/samples-20260717/issues/23) | Verify Herd SQLite extension mechanics; superseded by PostgreSQL pivot        |
| [24](https://github.com/s-a-c/samples-20260717/issues/24) | Decide UUIDv7 implementation strategy                                         |
| [25](https://github.com/s-a-c/samples-20260717/issues/25) | Decide Source Identity Registry survival across resets                        |
| [26](https://github.com/s-a-c/samples-20260717/issues/26) | Decide Spatie Permission, Shield, Fortify, and Team coexistence               |
| [27](https://github.com/s-a-c/samples-20260717/issues/27) | Re-verify upstream sample datasets                                            |
| [28](https://github.com/s-a-c/samples-20260717/issues/28) | Decide Product Import pipeline shape                                          |
| [29](https://github.com/s-a-c/samples-20260717/issues/29) | Decide Product Reset semantics, evidence, confirmation, and recovery          |
| [30](https://github.com/s-a-c/samples-20260717/issues/30) | Decide Filament resource generation strategy                                  |
| [31](https://github.com/s-a-c/samples-20260717/issues/31) | Decide Search Document shape and Search Surface rules                         |
| [32](https://github.com/s-a-c/samples-20260717/issues/32) | Decide Search Projection and trigger design                                   |
| [33](https://github.com/s-a-c/samples-20260717/issues/33) | Decide Embedding Profile implementation with `laravel/ai`                     |
| [34](https://github.com/s-a-c/samples-20260717/issues/34) | Decide Hybrid Retrieval/RRF and Federated Search                              |
| [35](https://github.com/s-a-c/samples-20260717/issues/35) | Decide Portfolio Card architecture and snapshot view                          |
| [36](https://github.com/s-a-c/samples-20260717/issues/36) | Decide Team Artefact schema and Team Owner boundary                           |
| [37](https://github.com/s-a-c/samples-20260717/issues/37) | Decide Implementation-Readiness Dossier format and generation                 |
| [38](https://github.com/s-a-c/samples-20260717/issues/38) | Decide documentation set and lifecycle                                        |
| [39](https://github.com/s-a-c/samples-20260717/issues/39) | Decide git branch, PR, and dependency-pinning strategy                        |
| [40](https://github.com/s-a-c/samples-20260717/issues/40) | Select PostgreSQL, pgvector, and tsvector stack                               |
| [41](https://github.com/s-a-c/samples-20260717/issues/41) | Decide PostgreSQL schema design for three products                            |
| [42](https://github.com/s-a-c/samples-20260717/issues/42) | Decide PostgreSQL extension management                                        |

### 2.4. Completed Remediation: Issues 46–63

| Issue                                                     | Completed task                                          |
| --------------------------------------------------------- | ------------------------------------------------------- |
| [46](https://github.com/s-a-c/samples-20260717/issues/46) | Annotate PHPStan baseline entries with ticket citations |
| [47](https://github.com/s-a-c/samples-20260717/issues/47) | Add pgvector CI service and enable coverage             |
| [48](https://github.com/s-a-c/samples-20260717/issues/48) | Recover ADR documentation                               |
| [49](https://github.com/s-a-c/samples-20260717/issues/49) | Gap remediation map                                     |
| [51](https://github.com/s-a-c/samples-20260717/issues/51) | Add missing `arch()` rules                              |
| [52](https://github.com/s-a-c/samples-20260717/issues/52) | Add dedicated Composer test scripts                     |
| [53](https://github.com/s-a-c/samples-20260717/issues/53) | Refactor Domain structure                               |
| [54](https://github.com/s-a-c/samples-20260717/issues/54) | Configure Infection mutation testing                    |
| [55](https://github.com/s-a-c/samples-20260717/issues/55) | Create Team Artefacts migration and model               |
| [56](https://github.com/s-a-c/samples-20260717/issues/56) | Migrate `EmbeddingJob` to `laravel/ai`                  |
| [57](https://github.com/s-a-c/samples-20260717/issues/57) | Create Implementation-Readiness Dossier command         |
| [58](https://github.com/s-a-c/samples-20260717/issues/58) | Create portfolio snapshot view migration                |
| [59](https://github.com/s-a-c/samples-20260717/issues/59) | Configure Mago                                          |
| [60](https://github.com/s-a-c/samples-20260717/issues/60) | Configure Rector                                        |
| [61](https://github.com/s-a-c/samples-20260717/issues/61) | Add unit tests for core services                        |
| [62](https://github.com/s-a-c/samples-20260717/issues/62) | Document macOS Herd PHPStan quirk and quality tools     |
| [63](https://github.com/s-a-c/samples-20260717/issues/63) | Update CONTEXT.md to remove SQLite terminology          |

### 2.5. Completed Admin Decisions: Issues 65–69

| Issue                                                     | Completed task                                          |
| --------------------------------------------------------- | ------------------------------------------------------- |
| [65](https://github.com/s-a-c/samples-20260717/issues/65) | Decide Admin surfaces for Import Data and Refresh Stats |
| [66](https://github.com/s-a-c/samples-20260717/issues/66) | Research running `product:import` from a web request    |
| [67](https://github.com/s-a-c/samples-20260717/issues/67) | Decide who can trigger Admin imports                    |
| [68](https://github.com/s-a-c/samples-20260717/issues/68) | Design stats refresh behavior                           |
| [69](https://github.com/s-a-c/samples-20260717/issues/69) | Design import button UX                                 |

### 2.6. Completed Pest/CI Work: Issues 74–80

| Issue                                                     | Completed task                                     |
| --------------------------------------------------------- | -------------------------------------------------- |
| [74](https://github.com/s-a-c/samples-20260717/issues/74) | Research Pest 5 feature surface and flags          |
| [75](https://github.com/s-a-c/samples-20260717/issues/75) | Decide Pest-direct Composer script migration       |
| [76](https://github.com/s-a-c/samples-20260717/issues/76) | Decide TIA configuration                           |
| [77](https://github.com/s-a-c/samples-20260717/issues/77) | Decide sharding configuration                      |
| [78](https://github.com/s-a-c/samples-20260717/issues/78) | Decide type-coverage gate shape                    |
| [79](https://github.com/s-a-c/samples-20260717/issues/79) | Build PR TIA/sharded coverage CI                   |
| [80](https://github.com/s-a-c/samples-20260717/issues/80) | Build advisory PR and blocking nightly mutation CI |

### 2.7. Existing Open Work

| Issue                                                                                                             | Treatment                                                                          |
| ----------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| [50 — Create Northwind Filament resources](https://github.com/s-a-c/samples-20260717/issues/50)                   | Remains the identity of the Northwind resource deliverable; Task 6 coordinates it. |
| [70 — Spec: Admin UI Product Import & Refresh Stats Buttons](https://github.com/s-a-c/samples-20260717/issues/70) | Remains the approved Admin specification; Task 9 executes and verifies it.         |

---

## 3. File and Responsibility Map

### 3.1. Existing files to modify

- `database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php`: single source of truth for portfolio-view DDL.
- `app/Services/ProductImport/{Chinook,Northwind,Pagila}Importer.php`: staging/source lifecycle, atomic publish, view recreation, cleanup.
- `app/Services/ProductImport/ProductImportPipeline.php`: phases, evidence, invariant results, embedding drain, and ResetRun transitions.
- `app/Services/ProductImport/SourceIdentityRegistry.php`: stable source-key/UUID behavior where tests expose gaps.
- `app/Observers/Tier1SourceObserver.php`: scoped staging suppression only; no permanent observer bypass.
- Product schema migrations under `database/migrations/{chinook,northwind,pagila}/`: migration-built staging/replay and schema-local trigger definitions.
- `app/Filament/Admin/Widgets/ProductPortfolioCard.php`, Admin pages, and related Blade views: lifecycle-correct UI behavior.
- `tests/Feature/Import/`, `tests/Feature/Reset/`, `tests/Feature/Search/`, and `tests/Feature/Admin/`: regression and acceptance seams.
- `CONTEXT.md`, ADRs under `docs/10-architecture/1003-adr/`, and dossier files under `docs/15-delivery/1515-implementation-readiness-dossier/`.

### 3.2. New files to create

- `app/Services/ProductImport/PortfolioViewRecreator.php`.
- `app/Services/ProductImport/StagingSchemaBuilder.php` and `app/Console/Commands/ProductStage.php`.
- `app/Domain/Staging/{Chinook,Northwind,Pagila}/` staging models.
- `app/Services/ProductImport/Mapping/{TableMapper,SelfReferentialMapper,ProductMapper}.php` and per-product mapper families.
- `app/Services/ProductImport/EmbeddingDrain.php`: post-publish pending-embedding dispatch, drain polling, and terminal-state reporting.
- Product fixture files and focused lifecycle/transform/search/reset tests.
- Golden Search Corpus fixture and acceptance test.
- ADR clarification and updated dossier evidence files where existing records are incomplete.

---

## 4. Phase 0 — Baseline and Governance

### 4.1. Task 0: Audit roadmap status and acceptance evidence

**Tracker:** [Audit roadmap status and acceptance evidence](https://github.com/s-a-c/samples-20260717/issues/86) · Beads `samples-20260717-7rg.1`.

**Depends on:** none.

**Files/artifacts:** GitHub issues #1, #15, #49, #64, #73; `docs/10-architecture/1003-adr/`; `docs/15-delivery/1515-implementation-readiness-dossier/`; current database state; this plan.

- [ ] **Step 1: Build the status matrix**

Record every historical issue from the Current-State Ledger with four columns: tracker status, code/document status, focused verification command, and acceptance evidence location.

- [ ] **Step 2: Verify the current database boundary**

Run:

```bash
php artisan migrate:status --no-ansi
php artisan db:show --database=pgsql --counts --views --no-ansi
php artisan pgsql:check
```

Expected: the report explicitly records whether `public.product_portfolio_snapshots`, product schemas, `search_projections`, and extensions exist; a missing view is a failing lifecycle evidence row, not a migration-status success.

- [ ] **Step 3: Reconcile dossier and ADR status**

Update only the roadmap-linked evidence references after the implementation tasks establish facts. Do not mark a stage `Pass` while its operator evidence or recovery command is absent.

- [ ] **Step 4: Post the audit resolution**

Comment the matrix and evidence paths on the tracker issue, mirror the execution bead notes, and link the result from map #85.

**Gate:** the roadmap has one status vocabulary and every historical task has a known evidence state.

---

## 5. Phase 1 — Runtime and Schema Safety

### 5.1. Task 1: Restore portfolio view after shadow-schema publish

**Tracker:** [Restore portfolio view after shadow-schema publish](https://github.com/s-a-c/samples-20260717/issues/87) · Beads `samples-20260717-7rg.2`.

**Depends on:** Task 0.

**Files:**

- Create: `app/Services/ProductImport/PortfolioViewRecreator.php`.
- Modify: `database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php`.
- Modify: `app/Services/ProductImport/ChinookImporter.php`.
- Modify: `app/Services/ProductImport/NorthwindImporter.php`.
- Modify: `app/Services/ProductImport/PagilaImporter.php`.
- Create: `tests/Feature/Import/PortfolioViewRecreatorTest.php`.
- Modify: `tests/Feature/Filament/ProductPortfolioSnapshotTest.php`.

**Interfaces:**

- `PortfolioViewRecreator::recreate(): void` executes the exact view DDL.
- Each importer calls the recreator inside the post-swap publish transaction after the renamed live schema exists.

- [ ] **Step 1: Write the red recreation test**

```php
it('recreates the portfolio view after it is dropped', function () {
    DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBeNull();

    app(PortfolioViewRecreator::class)->recreate();

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');
});
```

- [ ] **Step 2: Run the red test**

Run: `php artisan test --compact --filter=PortfolioViewRecreator`

Expected: FAIL because `PortfolioViewRecreator` does not exist.

- [ ] **Step 3: Extract the exact migration DDL**

Move the existing `CREATE OR REPLACE VIEW` SQL into the service constant/method. Refactor the migration to call the same implementation so fresh installs and post-swap repairs cannot drift.

- [ ] **Step 4: Add the post-swap call**

Call `app(PortfolioViewRecreator::class)->recreate()` after each `ALTER SCHEMA ... RENAME TO ...` and before the publish transaction commits. Keep the schema drop in the importer because ADR 100308 requires the shadow swap; recreate the public dependent explicitly.

- [ ] **Step 5: Add the lifecycle regression**

Run each importer with a minimal source/staging fixture, then call `PortfolioSnapshotStats::byProduct()` and assert the three product keys. This test must reproduce the original missing-view symptom if the recreator call is removed.

- [ ] **Step 6: Verify**

Run:

```bash
php artisan test --compact --filter='PortfolioViewRecreator|ProductPortfolioSnapshot'
vendor/bin/pint --dirty --format agent
composer phpstan:analyze
```

**Gate:** the view exists after fresh migration and after each product publish; `/admin` can read stats after import.

### 5.2. Task 2: Build migration-backed staging and schema-safe projections

**Tracker:** [Build migration-backed staging and schema-safe projections](https://github.com/s-a-c/samples-20260717/issues/88) · Beads `samples-20260717-7rg.3`.

**Depends on:** Task 0.

**Files:** product schema migrations, `app/Services/ProductImport/StagingSchemaBuilder.php`, `app/Console/Commands/ProductStage.php`, trigger migrations, `tests/Feature/Import/StagingSchemaBuilderTest.php`, `tests/Feature/Import/SearchTriggerSchemaTest.php`, `tests/Feature/Import/SchemaPreservationTest.php`.

- [ ] **Step 1: Define the staging contract in tests**

Assert that `product:stage chinook` creates empty app-shaped staging tables with UUID keys, generated `document_tsv`, search projections, indexes, and schema-local triggers. Assert that a staging insert writes to `chinook_staging.search_projections`, never `chinook.search_projections`.

- [ ] **Step 2: Replay schema migrations into staging**

Implement `StagingSchemaBuilder::build(string $product): void` using the existing schema definitions rather than upstream dump structure. Keep `<product>_source` upstream-shaped and `<product>_staging` app-shaped.

- [ ] **Step 3: Rewrite trigger functions**

Use `TG_TABLE_SCHEMA` and `format('%I...', TG_TABLE_SCHEMA)` for projection writes and related-table lookups. Cover the exact tier-1/tier-2 mapping from decision #31; do not hardcode the live product schema in a function intended for staging.

- [ ] **Step 4: Define cleanup paths**

Drop stale source/staging schemas on successful publish and on pre-publish failure. Keep the live schema untouched until validation succeeds. Assert cleanup in tests.

- [ ] **Step 5: Verify**

Run:

```bash
php artisan test --compact --filter='StagingSchemaBuilder|SearchTriggerSchema|SchemaPreservation'
composer test:arch
vendor/bin/pint --dirty --format agent
```

**Gate:** staging is structurally complete and schema-local; the portfolio view and search projection objects remain available after a swap.

---

## 6. Phase 2 — Import and Reset Correctness

### 6.1. Task 3: Implement Eloquent staging transform infrastructure

**Tracker:** [Implement Eloquent staging transform infrastructure](https://github.com/s-a-c/samples-20260717/issues/89) · Beads `samples-20260717-7rg.4`.

**Depends on:** Task 2.

**Files:** `app/Domain/Staging/`, `app/Services/ProductImport/Mapping/`, `app/Observers/Tier1SourceObserver.php`, `tests/Unit/Staging/`, `tests/Unit/Mapping/`, `tests/Feature/Import/SourceIdentityDuringRunTest.php`.

- [ ] **Step 1: Prove the staging write boundary fails before implementation**

Create a test with an active `ResetRun` that saves a staging model. Expected red result: the live-domain trait blocks the write or the staging model is missing.

- [ ] **Step 2: Create staging model subclasses**

Each subclass targets `<product>_staging.<table>` and omits `BelongsToProductDomain`. Keep the live model trait unchanged and add an architecture test that scopes the trait mandate to live models.

- [ ] **Step 3: Wire observer suppression**

Bind `is_staging=true` only around staging writes and `forgetInstance('is_staging')` in a `finally` path. Add a Queue fake test proving staging saves do not dispatch `EmbeddingJob`.

- [ ] **Step 4: Implement mapper bases**

Use these signatures:

```php
abstract class TableMapper
{
    public function load(string $sourceSchema, string $stagingSchema): int;
}

abstract class SelfReferentialMapper extends TableMapper
{
    public function load(string $sourceSchema, string $stagingSchema): int;
}

abstract class ProductMapper
{
    /** @return array{tables: int, rows: int} */
    public function load(string $sourceSchema, string $stagingSchema): array;
}
```

Read source rows from `<product>_source`; save staging rows through Eloquent staging subclasses; resolve IDs/FKs through `SourceIdentityRegistry`; use two passes for non-deferrable self-FKs and one transaction for Pagila's circular FK.

- [ ] **Step 5: Verify**

Run:

```bash
php artisan test --compact --filter='StagingModel|SourceIdentityDuringRun|TableMapper|SelfReferentialMapper|ProductMapper'
vendor/bin/pint --dirty --format agent
composer phpstan:analyze
composer test:arch
```

**Gate:** no raw domain-table insert path remains in the transform; staging writes are allowed, live writes remain guarded, and observers are correctly suppressed.

### 6.2. Task 4: Complete Chinook import mapping and acceptance

**Tracker:** [Complete Chinook import mapping and acceptance](https://github.com/s-a-c/samples-20260717/issues/90) · Beads `samples-20260717-7rg.5`.

**Depends on:** Task 3.

**Files:** Chinook mapper family, `tests/Fixtures/Sources/chinook/minimal.sql`, `tests/Feature/Import/TransformChinookTest.php`, `tests/Feature/Import/ImportersTest.php`.

- [ ] **Step 1: Add the minimal fixture**

Include artists, albums, genres, media types, employees with a self-reference, customers, tracks, playlists, playlist tracks, invoices, and invoice lines using upstream source keys.

- [ ] **Step 2: Add red transform tests**

Assert UUID PKs, source identity reuse, FK translation, casts, self-reference resolution, projection rows, and row counts.

- [ ] **Step 3: Implement concrete mappers**

Use `ChinookProductMapper` dependency order and the shared mapper interfaces from Task 3. Keep unsupported upstream fields out of the target rather than writing undocumented columns.

- [ ] **Step 4: Execute publish and evidence**

Run the fixture through source load, staging transform, invariant validation, swap, portfolio-view recreation, and embedding drain. Persist the Baseline Evidence Fixture.

- [ ] **Step 5: Verify**

Run: `php artisan test --compact --filter='TransformChinook|Importers'`.

**Gate:** Chinook passes all import invariants and remains queryable through portfolio, panel, and search paths after publish.

### 6.3. Task 5: Complete Northwind import mapping and resources

**Tracker:** [Complete Northwind import mapping and resources](https://github.com/s-a-c/samples-20260717/issues/91) · Beads `samples-20260717-7rg.6` · coordinates with [T8: Create Northwind Filament resources](https://github.com/s-a-c/samples-20260717/issues/50).

**Depends on:** Task 3.

**Files:** Northwind mapper family, Northwind fixture/test, `app/Filament/Northwind/Resources/`, `tests/Feature/Filament/NorthwindResourcesTest.php`, Northwind policy/architecture rules.

- [ ] **Step 1: Complete the seven resource acceptance tests**

Cover Category, Customer, Employee, Order, Product, Shipper, and Supplier discovery, panel ownership, authorization, navigation, and basic table/form behavior.

- [ ] **Step 2: Add transform fixture/tests**

Cover string/source keys, products/categories, orders/details, employees, binary fields, and source identity reuse.

- [ ] **Step 3: Implement mapper family**

Use the shared mapper infrastructure and ensure every FK points to translated UUIDs in migration-built staging.

- [ ] **Step 4: Verify**

Run:

```bash
php artisan test --compact --filter='NorthwindResources|TransformNorthwind'
composer test:arch
```

**Gate:** Northwind imports and resources work on PostgreSQL with the approved panel/permission boundaries.

### 6.4. Task 6: Complete Pagila import mapping and normalization

**Tracker:** [Complete Pagila import mapping and normalization](https://github.com/s-a-c/samples-20260717/issues/92) · Beads `samples-20260717-7rg.7`.

**Depends on:** Task 3.

**Files:** Pagila mapper family, `tests/Fixtures/Sources/pagila/`, `tests/Feature/Import/TransformPagilaTest.php`, Pagila model/resource tests.

- [ ] **Step 1: Verify normalized address schema**

Run `php artisan test --compact --filter=PagilaAddressesSchema` and preserve the existing address/FK acceptance.

- [ ] **Step 2: Add Pagila fixtures/tests**

Cover films, categories, languages, actors, film relationships, customers, staff, stores, rentals, payments, normalized addresses, and the staff/store circular FK.

- [ ] **Step 3: Implement mapper family**

Use one transaction for the deferrable circular relationship and translate all composite/source identities consistently.

- [ ] **Step 4: Verify**

Run:

```bash
php artisan test --compact --filter='PagilaAddressesSchema|TransformPagila'
composer test:arch
```

**Gate:** Pagila imports with normalized addresses, valid circular FKs, projections, portfolio view, and product isolation.

### 6.5. Task 7: Complete reset evidence, invariants, embedding drain, and recovery

**Tracker:** [Complete reset evidence invariants embedding drain and recovery](https://github.com/s-a-c/samples-20260717/issues/93) · Beads `samples-20260717-7rg.8`.

**Depends on:** Tasks 1, 4, 5, and 6.

**Files:** `app/Services/ProductImport/ProductImportPipeline.php`, `app/Services/ProductReset/ResetEvidence.php`, `app/Services/ProductReset/RecoveryService.php`, `app/Services/ProductImport/EmbeddingDrain.php`, product commands, `tests/Feature/Reset/`, `tests/Feature/Import/`.

- [ ] **Step 1: Assert evidence schema**

Test `schema_version`, requester, window, baseline, phases, invariants, counts, indexes, failure remediation hints, and `failure.stack_hash` serialization.

- [ ] **Step 2: Implement invariant evaluation**

Evaluate artifact digest, row counts, registry coverage, FK integrity, normalization, product isolation, projection population, and embedding lifecycle before marking a run succeeded.

- [ ] **Step 3: Implement post-publish drain**

Dispatch pending tier-1 embeddings after publish, wait for terminal states, record failures, and prevent success when pending/failed rows remain.

- [ ] **Step 4: Exercise recovery**

Fault inject staging, invariant, publish, embedding, and operator-abort failures. Assert parent/child `ResetRun`, `recovering`, window state, retry token, remediation hint, and final evidence.

- [ ] **Step 5: Verify**

Run:

```bash
php artisan test --compact --filter='Reset|Evidence|EmbeddingDrain|Recovery'
composer phpstan:analyze
composer test:arch
```

**Gate:** no import/reset can report success before derived data and evidence are complete; every failure has a safe recovery or explicit abort path.

---

## 7. Phase 3 — Product Panels and Admin Workflows

### 7.1. Task 8: Verify Admin import and stats lifecycle

**Tracker:** [Verify Admin import and stats lifecycle](https://github.com/s-a-c/samples-20260717/issues/94) · Beads `samples-20260717-7rg.9` · executes [Spec: Admin UI Product Import & Refresh Stats Buttons](https://github.com/s-a-c/samples-20260717/issues/70).

**Depends on:** Tasks 1 and 7.

**Files:** `app/Filament/Admin/Widgets/ProductPortfolioCard.php`, Admin Portfolio/dashboard views, `app/Jobs/ProductImportJob.php`, `tests/Feature/Admin/ProductCardActionsTest.php`, `tests/Feature/Filament/ProductPortfolioSnapshotTest.php`.

- [ ] **Step 1: Verify permission and action behavior**

Test super_admin import visibility, curator read-only behavior, confirmation details, job dispatch, ResetWindow rejection, and notifications.

- [ ] **Step 2: Verify live status/stats behavior**

Test running/succeeded/failed polling, cache invalidation, view recreation, independent product refresh, timestamp/pulse state, and automatic refresh after success.

- [ ] **Step 3: Run the browser/HTTP acceptance path**

Use Herd `samples-20260717.test`, authenticated operator session, `/admin`, and `/admin/portfolio`. Capture the request/result evidence without committing cookies or secrets.

- [ ] **Step 4: Verify**

Run:

```bash
php artisan test --compact --filter='ProductCardActions|ProductPortfolioSnapshot|Portfolio'
vendor/bin/pint --dirty --format agent
```

**Gate:** Admin is a real consumer of the lifecycle contract, not a fresh-migration-only demo.

---

## 8. Phase 4 — Search and Derived Read Models

### 8.1. Task 9: Complete search projections and Golden Search Corpus

**Tracker:** [Complete search projections and Golden Search Corpus](https://github.com/s-a-c/samples-20260717/issues/95) · Beads `samples-20260717-7rg.10`.

**Depends on:** Tasks 2, 4, 5, 6, and 7.

**Files:** trigger migrations, `app/Services/Search/`, `app/Jobs/EmbeddingJob.php`, search tests, corpus fixture.

- [ ] **Step 1: Cover all decision #31 mappings**

Assert every tier-1/tier-2 entity has the expected `weight_*_text`, `entity_type`, `embedding_state`, and generated `document_tsv` values.

- [ ] **Step 2: Verify lifecycle writes**

Exercise insert/update/delete on live and staging schemas, import publish, reset, recovery, embedding failure, and retry. Assert no live hardcoded-schema trigger writes from staging.

- [ ] **Step 3: Add Golden Search Corpus**

Create reviewed fixture expectations for product-scoped lexical, lexical-only, semantic/hybrid, federated labels, and deep links. Use exact assertions for lexical and top-k assertions for semantic/hybrid results.

- [ ] **Step 4: Verify**

Run:

```bash
php artisan test --compact --filter='Search|GoldenSearchCorpus|Embedding'
composer phpstan:analyze
```

**Gate:** search remains correct after data lifecycle operations and all product ownership/deep-link boundaries remain explicit.

---

## 9. Phase 5 — Quality, CI, and Documentation

### 9.1. Task 10: Reconcile ADRs, dossier, CI, and project documentation

**Tracker:** [Reconcile ADRs dossier CI and project documentation](https://github.com/s-a-c/samples-20260717/issues/96) · Beads `samples-20260717-7rg.11`.

**Depends on:** Tasks 0, 1, 7, 8, and 9.

**Files:** `CONTEXT.md`, ADR 100308/100314 companions, `docs/15-delivery/1515-implementation-readiness-dossier/`, `.github/workflows/tests.yml`, `.github/workflows/tia-baseline.yml`, `.github/workflows/mutation.yml`, `composer.json`, `README.md`.

- [ ] **Step 1: Add the schema-dependent recreation clarification**

Document that the portfolio view is a public cross-schema dependent and that post-swap recreation is a required publish step under ADR 100308 Decision 34.

- [ ] **Step 2: Add the staging trait-boundary clarification**

Document that `BelongsToProductDomain` applies to live models and staging subclasses are the explicit import-only exemption; record observer suppression and post-publish drain.

- [ ] **Step 3: Reconcile CONTEXT and dossier**

Remove stale SQLite/80%-only claims where superseded; add the actual PostgreSQL, view, projection, embedding, reset, Admin, and recovery commands/evidence locations. Stage statuses may only say Pass after evidence exists.

- [ ] **Step 4: Verify CI and scripts**

Run:

```bash
composer validate --no-check-publish
composer install --dry-run --no-interaction --no-progress
composer test:arch
composer phpstan:analyze
pnpm build
```

Parse all workflow YAML and confirm the Pest TIA/shard, coverage/type-coverage, mutation, and artifact paths match the installed tools.

**Gate:** documentation and automation describe the actual application, not the earlier SQLite or migration-only architecture.

---

## 10. Phase 6 — Release Acceptance and Operations

### 10.1. Task 11: Run two-environment release acceptance and sign-off

**Tracker:** [Run two-environment release acceptance and sign-off](https://github.com/s-a-c/samples-20260717/issues/97) · Beads `samples-20260717-7rg.12`.

**Depends on:** Tasks 5, 8, 9, and 10; coordinates with Task 6's existing Northwind resource issue.

**Files/evidence:** dossier stage files, CI artifacts, Herd evidence, Baseline Evidence Fixtures, Reset Isolation Proof, Authorization Acceptance Matrix, Golden Search Corpus.

- [ ] **Step 1: Herd acceptance**

Run fresh migration/seed, `operator:create`, source fetch, one real import per product, Admin dashboard/Portfolio load, product panels, search corpus, reset success, fault injection, recovery, and view/projection checks.

- [ ] **Step 2: Linux CI acceptance**

Run the full PostgreSQL/pgvector suite, Pest architecture/type/line coverage, TIA/shards, mutation workflows, import fixtures, projection/search tests, and artifact uploads.

- [ ] **Step 3: Fill the evidence families**

Attach repeatable evidence for baseline manifests/counts, reset isolation/fault recovery, role × action × panel authorization, and product/federated search behavior.

- [ ] **Step 4: Sign off or reopen the correct phase**

If any gate fails, reopen the owning task and record the exact failure/recovery command. Do not close the roadmap with an unowned evidence gap.

**Gate:** every ADR 100334 gate passes in both environments and no dependent view, projection, embedding, reset state, or Admin surface disappears after a real publish.

---

## 11. Phase 7 — Closure and Stabilization

The core Tasks 0–11 are implemented. Closure and acceptance are tracked by
GitHub #86, #87, #88, #89, #90, #91, #92, #93, #94, #95, #96, #97, #101,
#102, #103, #104, #105, #106, #108, and #111, mirrored by exactly 20 direct closed Beads
children under `samples-20260717-7rg`. GitHub issue #85 remains open as the
living Wayfinder map; Beads records execution state and does not replace the
map.

### 11.1. Task 12: Teams and Settings Livewire verification — **verified locally**

**Tracker:** [#101](https://github.com/s-a-c/samples-20260717/issues/101) · Beads
`samples-20260717-7rg.13`.

The failures were caused by an ignored `bootstrap/cache/routes-v7.php` generated
under `.env` APP_KEY (`livewire-e2bba137`) while tests used `.env.testing`
(`livewire-855ec315`). Clearing the route cache restored the generated update
endpoint. No application code change was required.

**Evidence:** `php artisan route:clear --no-interaction`; Teams/Settings
focused suite passed; the current hydrated full Pest suite is **592/592**
with **1,972 assertions**.

### 11.2. Task 13: Admin ProductCardActions verification — **verified locally**

**Tracker:** [#102](https://github.com/s-a-c/samples-20260717/issues/102) · Beads
`samples-20260717-7rg.14`.

The widget already had the required Filament action/schema traits. Its null
component and 404 symptoms had the same stale Livewire route-cache cause.

**Evidence:** `php artisan route:clear --no-interaction`; ProductCardActions
focused tests pass; the current hydrated full Pest suite is **592/592** with
**1,972 assertions**.

### 11.3. Task 14: Real source-data imports through the production pipeline

**Tracker:** [#103](https://github.com/s-a-c/samples-20260717/issues/103) · Beads
`samples-20260717-7rg.15`.

**Implementation files:** `app/Services/ProductImport/{ChinookImporter,NorthwindImporter,PagilaImporter,ProductImportPipeline,PostgresSourceReader}.php`, `app/Services/ProductImport/Schema/`, `app/Services/ProductImport/Mapping/`, and the related Pest suites.

- [x] Build isolated `<product>_source` schemas and load complete PostgreSQL
      dumps without semicolon-splitting function bodies.
- [x] Build migration-backed `<product>_staging` schemas and invoke the product
      mappers before atomic publish.
- [x] Recreate the portfolio view, evaluate invariants, and drain embeddings.
- [x] Fetch pinned upstream data with `php artisan source:fetch {product}`.
- [x] Run the production pipeline for all three products locally.

**Local evidence:** pinned fetch and import runs succeeded for all three
products. Chinook published 275 artists, 347 albums, 3,503 tracks, 412
invoices, and 4,652 search projections. Northwind source-to-target parity is
confirmed for 8 categories, 91 customers, 9 employees, 4 regions, 53
territories, 49 employee-territory links, 6 shippers, 29 suppliers, 77
products, 830 orders, 2,155 order details, and 1,107 search projections.
Pagila published 1,000 films, 200 actors, 599 customers, and 2,534 search
projections. Each reset run reached `succeeded` / `complete`, and the
portfolio view reports all three products. The source reader restores the
caller's PostgreSQL `search_path` after each dump.

**Evidence status:** implementation and local operator verification are
complete. The release record points to the merged implementation SHAs
`420434c8ae1f811d97c34a2d62f222479f02cb51` (PR #107) and
`4210e5bfaa865e183559a7c81260b555306b85f6` (PR #110). Current local checks
also cover the post-merge configuration fix, Northwind mapper completion, and
CI package-manager alignment on committed follow-up SHA
`c03a8c8f73e546f7e6848e127e1cee5a5cae6c9e` in PR #126.

### 11.4. Task 15: Linux CI with pgvector/pgvector:pg18

**Tracker:** [#104](https://github.com/s-a-c/samples-20260717/issues/104) · Beads
`samples-20260717-7rg.16`.

**Workflow changes:** `.github/workflows/tests.yml`,
`.github/workflows/tia-baseline.yml`, and `.github/workflows/mutation.yml` keep
the `pgvector/pgvector:pg18` service and now take pnpm from the single
`package.json` `packageManager` declaration. This removes the duplicate
pnpm-version failure seen in the scheduled TIA and mutation runs on
`c5387f354c9ad24e61f3ced8748fc01d87760fd3`.

**Merged evidence:** PR #107 and PR #110 passed their required Linux checks,
including PostgreSQL/pgvector tests, coverage, TIA shards, mutation, PHPStan,
Pint, CodeQL, and Semgrep, on their respective merge SHAs above.

**Current follow-up:** PR #126 on
`c03a8c8f73e546f7e6848e127e1cee5a5cae6c9e` is the current committed PR head;
remote required checks passed. The PR remains a follow-up change awaiting
normal review/merge and is not a reason to close Wayfinder map #85.

### 11.5. Task 17: PHPStan quality gate — **verified locally**

**Tracker:** [#106](https://github.com/s-a-c/samples-20260717/issues/106) · Beads
`samples-20260717-7rg.18` — tracker-closed.

- [x] Fix code-level strict-analysis violations and remove stale unmatched
      baseline entries.
- [x] Retain only documented framework-idiom exceptions.
- [x] Verify `composer phpstan:analyze` / the direct PHPStan command exits 0.

**Evidence:** PHPStan 0 errors; hydrated Pest 592/592; Pint pass; Mago guard
pass; Architecture 26/26 on the current checkout. The strict error fixed in
this reconciliation was the optional PostgreSQL direct-connection filter in
`config/database.php`.

### 11.6. Task 18: Coverage gate remediation — **verified**

**Tracker:** [#108](https://github.com/s-a-c/samples-20260717/issues/108) · Beads
`samples-20260717-7rg.19` — tracker-closed.

- [x] Add meaningful tests for uncovered source-schema, dump-reader, mapper,
      and import-orchestration paths.
- [x] Keep the configured 100% line-coverage threshold; do not lower it or
      blanket-exclude roadmap code.
- [x] Verify the coverage command exits 0 on the committed SHA and Linux CI.

**Evidence:** PR #107 Linux Coverage and type coverage pass at 100%; PR #126
also passes coverage, both TIA shards, and the mutation pull-request job on
`c03a8c8f73e546f7e6848e127e1cee5a5cae6c9e`.

### 11.7. Task 16: Documentation and acceptance-record alignment

**Tracker:** [#105](https://github.com/s-a-c/samples-20260717/issues/105) · Beads
`samples-20260717-7rg.17`.

- [x] Reconcile this plan and the roadmap spec with the final implementation
      and verification states.
- [x] Update Wayfinder map #85, GitHub resolution comments, Beads notes, and
      the implementation-readiness dossier.
- [x] Update applicable ADR, CONTEXT, operator/recovery, and quality-gate
      references when behavior or commands changed.
- [x] Run targeted documentation diff/parity checks; retain the existing
      full-tree validator findings as a separately scoped documentation
      cleanup queue.

**Gate:** documents distinguish implemented, verified, and accepted states;
current automated evidence points to committed SHAs; and PR #126 has no
pending or failing required checks.

## 12. Historical Task Reconciliation Rules

- Do not reopen issues 2–42 merely because their decisions are old; reopen only for a material implementation conflict and link the new evidence.
- Do not treat issues 47–63 as final acceptance; use Task 0 to classify which are implemented, verified, or only documented.
- Keep issues 50 and 70 as the identity of their existing work; Task 5 and Task 8 extend/verify them rather than creating duplicates.
- Keep issues 74–80 as historical Pest/CI work; Task 10 and Task 11 verify that the workflows are actually part of the release gate.
- Close each new GitHub issue only with a resolution comment containing files, commands, evidence locations, and the corresponding Beads ID.

## 13. Self-Review

### 13.1. Scope coverage

- Product architecture and prior decisions: Current-State Ledger.
- Missing view: Task 1.
- Schema/projection cascade: Task 2.
- Staging exemption and transform deviation: Task 3.
- Chinook/Northwind/Pagila mapping: Tasks 4–6.
- Reset Evidence, invariants, drain, recovery: Task 7.
- Admin lifecycle: Task 8.
- Search lifecycle and corpus: Task 9.
- ADR, dossier, CI, and documentation drift: Task 10.
- Herd/Linux final acceptance: Task 11.
- Completed tasks: all historical issue groups are listed with links.

### 13.2. Placeholder scan

Every remaining task has a tracker issue, files, interfaces, test command, and acceptance gate; no step is unowned or deferred behind an unspecified implementation choice.

### 13.3. Dependency consistency

- Task 1 and Task 2 depend on Task 0.
- Task 3 depends on Task 2.
- Tasks 4–6 depend on Task 3.
- Task 7 depends on Tasks 1 and 4–6.
- Task 8 depends on Tasks 1 and 7.
- Task 9 depends on Tasks 2–7 as needed by the search lifecycle.
- Task 10 depends on the implementation/evidence tasks.
- Task 11 is the final gate and consumes Tasks 5, 8, 9, and 10.
- Task 15 (CI verification) depends on Tasks 12 (Teams/Settings fix), 13 (Admin fix), and 14 (real imports).

## 14. Execution Handoff

Plan retained as the implementation and acceptance record at
`docs/superpowers/plans/2026-08-08-samples-application-delivery-roadmap.md`.
The canonical tracker is [Wayfinder — Samples Application Delivery Roadmap](https://github.com/s-a-c/samples-20260717/issues/85),
with 20 direct execution children mirrored by Beads parent
`samples-20260717-7rg`. #85 remains open so later evidence and follow-up work
can be added without creating a replacement map.
