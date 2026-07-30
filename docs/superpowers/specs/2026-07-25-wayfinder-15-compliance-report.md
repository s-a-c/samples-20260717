---
title: "Wayfinder #15 Compliance Report"
description: "> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)"
type: report
tags: \[report, specs, wayfinder, "15"]
updated: 2026-07-30
---

# Wayfinder #15 Compliance Report

> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)
> **Destination:** Production-ready Laravel 13 + PHP 8.5 application with Chinook, Northwind, and Pagila as three sample products
> **Date:** 2026-07-25
> **Status:** Substantially implemented with 10 known gaps across testing infrastructure, quality tooling, and 3 feature domains

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Executive Summary](#1-executive-summary)
- [2. Decision Cluster Compliance Detail](#2-decision-cluster-compliance-detail)
    - [2.1. ✅ 2.1 PostgreSQL Pivot (#40, #41, #42)](#21--21-postgresql-pivot-40-41-42)
    - [2.2. ✅ 2.2 UUIDv7 Strategy (#24)](#22--22-uuidv7-strategy-24)
    - [2.3. ✅ 2.3 Source Identity Registry (#25)](#23--23-source-identity-registry-25)
    - [2.4. ✅ 2.4 Product Import Pipeline (#28)](#24--24-product-import-pipeline-28)
    - [2.5. ✅ 2.5 Product Reset Semantics (#29)](#25--25-product-reset-semantics-29)
    - [2.6. ⚠️ 2.6 Filament Panels + Resources (#16, #30)](#26-️-26-filament-panels--resources-16-30)
    - [2.7. ⚠️ 2.7 Spatie + Shield + Fortify Coexistence (#26)](#27-️-27-spatie--shield--fortify-coexistence-26)
    - [2.8. ✅ 2.8 Search Domain (#31, #32, #33, #34)](#28--28-search-domain-31-32-33-34)
    - [2.9. ⚠️ 2.9 Portfolio Card (#35)](#29-️-29-portfolio-card-35)
    - [2.10. ❌ 2.10 Team Artefacts (#36)](#210--210-team-artefacts-36)
    - [2.11. ❌ 2.11 Implementation-Readiness Dossier (#37)](#211--211-implementation-readiness-dossier-37)
    - [2.12. ❌ 2.12 Architectural Decision Records (ADRs) — CRITICAL GAP](#212--212-architectural-decision-records-adrs--critical-gap)
- [3. Cross-Cutting Infrastructure Gaps](#3-cross-cutting-infrastructure-gaps)
    - [3.1. ⚠️ 3.1 Test Pyramid (#17) — CRITICAL GAPS](#31-️-31-test-pyramid-17--critical-gaps)
    - [3.2. ⚠️ 3.2 Larastan / PHPStan (#18)](#32-️-32-larastan--phpstan-18)
    - [3.3. ❌ 3.3 Rector — UNCONFIGURED](#33--33-rector--unconfigured)
    - [3.4. ❌ 3.4 Mago — UNCONFIGURED](#34--34-mago--unconfigured)
    - [3.5. ⚠️ 3.5 CI Pipeline (#11, #39)](#35-️-35-ci-pipeline-11-39)
    - [3.6. ❌ 3.6 CONTEXT.md SQLite Terminology Cleanup](#36--36-contextmd-sqlite-terminology-cleanup)
    - [3.7. ❌ 3.7 Domain Structure — Product Files Outside `app/Domain/`](#37--37-domain-structure--product-files-outside-appdomain)
    - [3.8. ❌ 3.8 ADR Documentation — Recovery Required](#38--38-adr-documentation--recovery-required)
- [4. Gap Summary (Prioritised)](#4-gap-summary-prioritised)
- [5. Row Counts](#5-row-counts)

</details>

---

## 1. Executive Summary

Wayfinder #15 resolved all 40+ decisions spanning the full architecture. The codebase has substantial implementation coverage across PostgreSQL schemas, UUIDv7 identity, product import/reset pipelines, Filament panels, hybrid search services, Spatie+Shield RBAC, and Fortify auth.

**Compliance by tier:**

| Tier                         | Description                 | Count                                   |
| ---------------------------- | --------------------------- | --------------------------------------- |
| ✅ Fully Compliant           | Implemented per spec        | 8 decision clusters                     |
| ⚠️ Partially Compliant       | Implemented with gaps       | 5 decision clusters                     |
| ❌ Missing / Not Implemented | Not yet built or configured | 3 feature domains + 3 quality-tool gaps |

---

## 2. Decision Cluster Compliance Detail

### 2.1. ✅ 2.1 PostgreSQL Pivot (#40, #41, #42)

| Requirement                                        | Status      | Evidence                                                        |
| -------------------------------------------------- | ----------- | --------------------------------------------------------------- |
| `CREATE EXTENSION vector`                          | ✅ Done     | `0001_01_01_000000_create_postgres_extensions.php`              |
| `CREATE EXTENSION unaccent`                        | ✅ Done     | Same migration                                                  |
| `CREATE EXTENSION pg_trgm`                         | ✅ Done     | Same migration                                                  |
| `en_unaccent` text search config                   | ✅ Done     | Same migration                                                  |
| Per-product schemas (chinook, northwind, pagila)   | ✅ Done     | Migrations in `database/migrations/{chinook,northwind,pagila}/` |
| Schema-qualified `#[Table]` attributes on models   | ✅ Done     | All domain models use `#[Table('schema.table')]`                |
| `vector(1024)` columns in search projections       | ✅ Done     | All 3 `*_search_projections` migrations use `vector(1024)`      |
| HNSW index (`m=16`, `ef_construction=64`)          | ✅ Done     | Search projection migrations                                    |
| GIN index on `document_tsv`                        | ✅ Done     | Search projection migrations                                    |
| Pest test `test_postgres_extensions_are_installed` | ✅ Done     | `tests/Feature/Postgres/PostgresExtensionsTest.php`             |
| `php artisan pgsql:check` command                  | ✅ Done     | `app/Console/Commands/PgsqlCheck.php`                           |
| CONTEXT.md SQLite term removal                     | ❌ Not done | Still references sqlite-vec, FTS5, vec0, etc.                   |

**Verdict: ✅ Compliant — one documentation cleanup task remains open**

---

### 2.2. ✅ 2.2 UUIDv7 Strategy (#24)

| Requirement                           | Status  | Evidence                                                      |
| ------------------------------------- | ------- | ------------------------------------------------------------- |
| Native `HasUuids` trait (no package)  | ✅ Done | All domain + shared models use `HasUuids`                     |
| Postgres native `uuid` column type    | ✅ Done | `$table->uuid('id')` in all app-owned tables                  |
| UUIDv7 PK on every app-owned table    | ✅ Done | Users, teams, domains, reset tables all UUID                  |
| Import hook = explicit-id-on-reuse    | ✅ Done | `SourceIdentityRegistry` sets ID on hit, leaves unset on miss |
| Starter migrations rewritten in place | ✅ Done | `users`, `teams` use `uuid('id')` + `foreignUuid()`           |

**Verdict: ✅ Fully compliant**

---

### 2.3. ✅ 2.3 Source Identity Registry (#25)

| Requirement                                          | Status  | Evidence                                                         |
| ---------------------------------------------------- | ------- | ---------------------------------------------------------------- |
| Single shared `public.source_identities` table       | ✅ Done | Migration `0001_01_01_000001_create_source_identities_table.php` |
| JSONB `source_key` for atomic + composite keys       | ✅ Done | `jsonb source_key not null`                                      |
| `entity` discriminator = schema-qualified table name | ✅ Done | Stored as `chinook.artists` etc.                                 |
| `product` is `GENERATED ALWAYS AS ... STORED`        | ✅ Done | Derived, drift-proof                                             |
| No FKs to product tables or reset_runs               | ✅ Done | Self-contained, no FK declarations                               |
| UUIDv7 PK directly                                   | ✅ Done | `$table->uuid('id')`                                             |
| `UNIQUE (entity, source_key)`                        | ✅ Done | Composite unique constraint                                      |
| Registry writes outside reset transaction            | ✅ Done | Immediate autocommit per import design                           |

**Verdict: ✅ Fully compliant**

---

### 2.4. ✅ 2.4 Product Import Pipeline (#28)

| Requirement                                        | Status  | Evidence                                                        |
| -------------------------------------------------- | ------- | --------------------------------------------------------------- |
| Unified pipeline (first-import = reset)            | ✅ Done | `ProductImportPipeline`                                         |
| Shadow-schema staging (`_staging` schema)          | ✅ Done | Staging swap pattern in pipeline                                |
| Per-product importer class                         | ✅ Done | ChinookImporter, NorthwindImporter, PagilaImporter              |
| Per-format source reader                           | ✅ Done | SqliteSourceReader, SqlSourceReader, PostgresSourceReader       |
| Topological sort + deferrable FK for Pagila circle | ✅ Done | Pagila `staff.store_id ↔ store.manager_staff_id`                |
| `product:import` artisan command                   | ✅ Done | `app/Console/Commands/ProductImportCommand.php`                 |
| `public.reset_runs` table (5-state machine)        | ✅ Done | `status` + `current_phase` + CHECK constraints                  |
| Recovery (recovery_of UUID)                        | ✅ Done | FK to parent `reset_runs`                                       |
| `--dry-run` mode                                   | ✅ Done | `kind='dry_run'`                                                |
| Per-product concurrency via advisory lock          | ✅ Done | `pg_try_advisory_xact_lock`                                     |
| Pin manifests as PHP files                         | ✅ Done | `database/sources/{chinook,northwind,pagila}.php`               |
| Source Artifact Cache                              | ✅ Done | `storage/app/private/sources/<product>/<commit_sha>/<filename>` |

**Verdict: ✅ Fully compliant**

---

### 2.5. ✅ 2.5 Product Reset Semantics (#29)

| Requirement                                             | Status  | Evidence                                                 |
| ------------------------------------------------------- | ------- | -------------------------------------------------------- |
| `App\Services\ProductReset\ResetWindow`                 | ✅ Done | `app/Services/ProductReset/ResetWindow.php`              |
| App-layer window check (no DB-level REVOKE)             | ✅ Done | Uses `EXISTS (SELECT 1 FROM reset_runs WHERE ...)`       |
| Defense-in-depth: Eloquent trait + Filament policy      | ✅ Done | `BelongsToProductDomain` trait with `assertWritable()`   |
| HTTP 423 Locked with `ProductResetWindowOpen` exception | ✅ Done | `app/Exceptions/ProductResetWindowOpen.php`              |
| `ResetEvidence` typed VO with `SCHEMA_VERSION = 1`      | ✅ Done | `app/Services/ProductReset/ResetEvidence.php`            |
| Reset Confirmation signed-token protocol                | ✅ Done | `ResetConfirmationService` + `reset_confirmations` table |
| 5min TTL, one-time use                                  | ✅ Done | In confirmation service                                  |
| `product:confirm` command                               | ✅ Done | `app/Console/Commands/ProductConfirm.php`                |
| `product:abort` command                                 | ✅ Done | `app/Console/Commands/ProductAbort.php`                  |
| `product:recover` command                               | ✅ Done | `app/Console/Commands/ProductRecover.php`                |
| `product:status` command                                | ✅ Done | `app/Console/Commands/ProductStatusCommand.php`          |
| Recovery runbook                                        | ✅ Done | Remediation hint enum + decision tree                    |

**Verdict: ✅ Fully compliant**

---

### 2.6. ⚠️ 2.6 Filament Panels + Resources (#16, #30)

| Requirement                                              | Status     | Evidence                                                  |
| -------------------------------------------------------- | ---------- | --------------------------------------------------------- |
| 4 PanelProviders installed                               | ✅ Done    | Admin, Chinook, Northwind, Pagila                         |
| No Filament tenancy used                                 | ✅ Done    | No `->tenant()` calls                                     |
| Fortify owns auth, Filament login disabled on all panels | ✅ Done    | All panels omit `->login()`                               |
| Per-panel discovery roots                                | ✅ Done    | Explicit `->discoverResources/Pages/Widgets` in each      |
| Shield in Admin only                                     | ✅ Done    | `FilamentShieldPlugin::make()` only in AdminPanelProvider |
| **Chinook Filament resources**                           | ✅ Done    | 8 resources                                               |
| **Pagila Filament resources**                            | ✅ Done    | 10 resources                                              |
| **Northwind Filament resources**                         | ❌ Missing | No `app/Filament/Northwind/` directory at all             |
| `operator:create` command                                | ✅ Done    | `app/Console/Commands/OperatorCreate.php`                 |
| Shield generation scope (admin only)                     | ✅ Done    | Per panel config                                          |
| Panel IDs/paths: admin→/admin, chinook→/chinook, etc.    | ✅ Done    | Defined in each panel provider                            |
| `canAccessPanel` role-matching pattern                   | ✅ Done    | `match($panel->getId())` pattern                          |
| Product-scoped roles (`chinook_curator`, etc.)           | ✅ Done    | Role names in permissions migration                       |

**Verdict: ⚠️ Northwind panel resources not yet created. Otherwise compliant.**

---

### 2.7. ⚠️ 2.7 Spatie + Shield + Fortify Coexistence (#26)

| Requirement                                                   | Status     | Evidence                                           |
| ------------------------------------------------------------- | ---------- | -------------------------------------------------- |
| Spatie is sole RBAC engine                                    | ✅ Done    | Spatie roles/permissions installed                 |
| Starter's TeamRole/TeamPermission reframed as membership data | ✅ Done    | Team enums kept, not turned into RBAC              |
| Spatie teams mode OFF                                         | ✅ Done    | No teams config in `config/permission.php`         |
| Role taxonomy: `super_admin`, `{product}_curator`             | ✅ Done    | Defined and assignable                             |
| 4-panel unauthenticated redirect to Fortify `/login`          | ✅ Done    | Panels omit `->login()`, fallback route to Fortify |
| Production operator onboarding via `operator:create`          | ✅ Done    | Artisan command + `ProvisionOperator` action       |
| Shield RoleResource for runtime role management               | ⚠️ Partial | RoleResource likely needed but not verified        |
| **PagilaPolicy missing**                                      | ❌ Missing | `app/Policies/Pagila/` directory does not exist    |
| `Pagila.php` stub model missing                               | ❌ Missing | Unlike `Chinook.php` and `Northwind.php` stubs     |
| `app/Policies/Pagila` directory                               | ❌ Missing | Not created                                        |

**Verdict: ⚠️ Pagila policy infrastructure missing. Core Spatie+Shield+Fortify coexistence functions correctly.**

---

### 2.8. ✅ 2.8 Search Domain (#31, #32, #33, #34)

| Requirement                                                | Status      | Evidence                                                   |
| ---------------------------------------------------------- | ----------- | ---------------------------------------------------------- |
| Tier 1 / Tier 2 / Tier 3 entity assignments                | ✅ Done     | Implemented in search projection triggers                  |
| Weight-class text columns (A/B/C/D)                        | ✅ Done     | All `search_projections` tables have weight_*_text columns |
| `document_tsv tsvector GENERATED ALWAYS AS ... STORED`     | ✅ Done     | In all search projection migrations                        |
| `vector(1024)` with `embedding_profile` + `content_digest` | ✅ Done     | In all search projection migrations                        |
| `embedding_state` lifecycle                                | ✅ Done     | `pending`/`complete`/`failed`/`mismatched`/`lexical_only`  |
| Postgres trigger maintains projection on source CRUD       | ✅ Done     | Search projection trigger in migrations                    |
| `Tier1SourceObserver` dispatches `EmbeddingJob`            | ✅ Done     | `app/Observers/Tier1SourceObserver.php`                    |
| `FederatedSearchService` with federated `UNION ALL`        | ✅ Done     | `app/Services/Search/FederatedSearchService.php`           |
| `ReciprocalRankFusion` (pure PHP, k=60)                    | ✅ Done     | `app/Services/Search/ReciprocalRankFusion.php`             |
| `SearchDeepLinkRegistry` static map                        | ✅ Done     | `app/Services/Search/SearchDeepLinkRegistry.php`           |
| `laravel/ai` SDK installed                                 | ✅ Done     | `composer.json` has `laravel/ai:^0.10.1`                   |
| `config/ai.php` configured                                 | ✅ Done     | OpenAI primary, OpenRouter fallback, 1024d                 |
| **EmbeddingJob uses `laravel/ai` SDK**                     | ❌ Not done | Uses raw HTTP directly to OpenAI API instead of AI SDK     |
| 3 retries with exponential backoff                         | ⚠️ Partial  | `EmbeddingJob` has `$tries=3` but no backoff               |
| `embedding_state='failed'` on exhaustion                   | ✅ Done     | Job catches exceptions and sets state                      |

**Verdict: ⚠️ EmbeddingJob bypasses the `laravel/ai` SDK (#33 decision); uses raw HTTP instead. Otherwise compliant.**

---

### 2.9. ⚠️ 2.9 Portfolio Card (#35)

| Requirement                                       | Status     | Evidence                                                   |
| ------------------------------------------------- | ---------- | ---------------------------------------------------------- |
| Filament Widget (`ProductPortfolioCard`)          | ✅ Done    | `app/Filament/Admin/Widgets/ProductPortfolioCard.php`      |
| Portfolio Dashboard page                          | ✅ Done    | `app/Filament/Admin/Pages/Portfolio.php`                   |
| Blade views for widget and page                   | ✅ Done    | `resources/views/filament/admin/widgets/...` + `pages/...` |
| 3-column layout, alphabetical sort                | ✅ Done    | `getColumns() → 3` in widget                               |
| Widget registered 3× (Chinook, Northwind, Pagila) | ✅ Done    | In AdminPanelProvider                                      |
| **`product_portfolio_snapshots` Postgres view**   | ❌ Missing | Migration not created — relies on live queries instead     |
| Widget reusable with `product` prop               | ✅ Done    | Single class, instantiated per product                     |

**Verdict: ⚠️ Portfolio card works but Postgres view for snapshot storage not created. Live queries used instead.**

---

### 2.10. ❌ 2.10 Team Artefacts (#36)

| Requirement                                  | Status     | Evidence             |
| -------------------------------------------- | ---------- | -------------------- |
| `team_artefacts` migration                   | ❌ Missing | No migration created |
| `TeamArtefact` Eloquent model                | ❌ Missing | Not created          |
| Saved Search configuration (query bookmarks) | ❌ Missing | Not implemented      |
| Team Dashboard configuration (card layout)   | ❌ Missing | Not implemented      |
| Polymorphic `type` enum                      | ❌ Missing | Not created          |

**Verdict: ❌ Entirely unimplemented. Decision was resolved but execution not started.**

---

### 2.11. ❌ 2.11 Implementation-Readiness Dossier (#37)

| Requirement                                               | Status     | Evidence                              |
| --------------------------------------------------------- | ---------- | ------------------------------------- |
| `dossier:generate` artisan command                        | ❌ Missing | Not created                           |
| `docs/15-delivery/1515-implementation-readiness-dossier/` | ❌ Missing | Directory exists but completely empty |
| Stage files (151502–151517)                               | ❌ Missing | Not generated                         |
| Stage status tracking in dossier                          | ❌ Missing | Not implemented                       |
| Navigation entry point `151501-contents.md`               | ❌ Missing | Not created                           |

**Verdict: ❌ Entirely unimplemented. Output directory is empty.**

---

### 2.12. ❌ 2.12 Architectural Decision Records (ADRs) — CRITICAL GAP

| Requirement                                                         | Status                   | Evidence                                                                                 |
| ------------------------------------------------------------------- | ------------------------ | ---------------------------------------------------------------------------------------- |
| ADRs for all resolved decisions                                     | ❌ **12 of ~17 missing** | Only 5 ADRs exist (Multi-Product, UUIDv7, Hybrid Search, Shadow Import, Panel Isolation) |
| ADRs at `docs/10-architecture/1015-adrs/`                           | ❌ Wrong path            | Currently at `docs/adr/` not the spec'd path                                             |
| **Missing ADR 0006: Source Identity Registry (#25)**                | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0007: Product Reset Semantics (#29)**                 | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0008: Spatie + Shield + Fortify (#26)**               | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0009: Search Document Shape + Federation (#31, #34)** | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0010: Embedding Profile + AI SDK (#33)**              | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0011: Portfolio Card Architecture (#35)**             | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0012: Team Artefacts Schema (#36)**                   | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0013: Test Pyramid (#17)**                            | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0014: Larastan Target Level + Baseline Policy (#18)** | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0015: Implementation-Readiness Dossier (#37)**        | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0016: Documentation Lifecycle (#38)**                 | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| **Missing ADR 0017: Git Branch + PR Strategy (#39)**                | ❌ Missing               | Decision resolved in wayfinder, no ADR written                                           |
| ADR directory structure                                             | ❌ Wrong path            | `docs/adr/` exists but spec requires `docs/10-architecture/1015-adrs/`                   |
| ADR README with index                                               | ⚠️ Partial               | README exists at `docs/adr/README.md` but references the old path                        |

**Verdict: ❌ 12 of 17 required ADRs are missing. ADRs must be recovered/restated from the Wayfinder #15 decision resolutions as a priority. Existing 5 ADRs must be moved to the correct path.**

---

## 3. Cross-Cutting Infrastructure Gaps

### 3.1. ⚠️ 3.1 Test Pyramid (#17) — CRITICAL GAPS

| Requirement                                                                                                                         | Status            | Evidence                                                          |
| ----------------------------------------------------------------------------------------------------------------------------------- | ----------------- | ----------------------------------------------------------------- |
| **Architecture tests**                                                                                                              | ⚠️ Partial        | 10 `arch()` rules exist; spec calls for **15+** rules             |
| Missing rule: ResetWindow is sole reset_runs reader                                                                                 | ❌ Missing        | Not tested                                                        |
| Missing rule: ResetConfirmation ownership                                                                                           | ❌ Missing        | Not tested                                                        |
| Missing rule: RecoveryService transition ownership                                                                                  | ❌ Missing        | Not tested                                                        |
| Missing rule: Import isolation (no sibling product cross-ref)                                                                       | ❌ Missing        | Not tested                                                        |
| Missing rule: No `DB::` from presentation layers (#7 from #17)                                                                      | ❌ Missing        | Not tested                                                        |
| Missing rule: No `app()`/`resolve()` in presentation (#11 from #17)                                                                 | ❌ Missing        | Not tested                                                        |
| **Mutation testing**                                                                                                                | ❌ Not configured | No infection.json, no mutation script                             |
| **80% line coverage floor** (`--min=80`)                                                                                            | ❌ Not configured | No coverage requirement in CI or composer scripts                 |
| **Type coverage (pest-plugin-type-coverage)**                                                                                       | ❌ Not configured | Package installed but no thresholds set                           |
| **Dedicated test scripts** (`test:unit`, `test:feature`, `test:pg`, `test:arch`, `test:coverage`, `test:mutation`, `test:type-cov`) | ❌ Missing        | Only `test`, `ci:check`, `types:check` exist                      |
| **Feature/Postgres test suite**                                                                                                     | ⚠️ Partial        | `PostgresExtensionsTest.php` exists but CI lacks pgvector service |
| **Livewire component tests**                                                                                                        | ✅ Done           | 7 test files with 64 Livewire calls                               |
| **Coverage in phpunit.xml**                                                                                                         | ❌ Missing        | No `<coverage>` element                                           |
| **Coverage in CI**                                                                                                                  | ❌ Disabled       | `coverage: none` in setup-php                                     |
| **Unit tests**                                                                                                                      | ❌ Minimal        | Only 1 unit test (ExampleTest)                                    |

**Verdict: ⚠️ 5 of 15+ architecture rules implemented. Mutation, coverage, type-coverage, and dedicated scripts all missing.**

---

### 3.2. ⚠️ 3.2 Larastan / PHPStan (#18)

| Requirement                                         | Status      | Evidence                                                  |
| --------------------------------------------------- | ----------- | --------------------------------------------------------- |
| `level: max`                                        | ✅ Done     | `phpstan.neon` level: max                                 |
| No baseline or shrinking carve-outs                 | ❌ Violated | `phpstan-baseline.neon` exists with ~150 baselined errors |
| Framework-idiom carve-outs documented               | ✅ Done     | `staticMethod.dynamicCall` + `missingType.generics`       |
| Carve-outs cite retiring ticket                     | ✅ Done     | Comments reference `bd .eac5a270.2`                       |
| `tests/` in paths                                   | ✅ Done     | Included                                                  |
| macOS Herd `--threads=1` quirk documented in README | ❌ Missing  | Not documented                                            |
| Composer `types:check` script                       | ✅ Done     | `phpstan analyse --memory-limit=512M`                     |

**Verdict: ⚠️ phpstan-baseline.neon exists with ~150 un-cited entries — directly contradicts #18's no-baseline policy. Per the #15 resolution comment, the premise was corrected: baseline intended as a shrinking list with per-entry ticket citations. Currently the baseline lists errors without any citation mechanism.**

---

### 3.3. ❌ 3.3 Rector — UNCONFIGURED

| Requirement                           | Status     | Evidence                   |
| ------------------------------------- | ---------- | -------------------------- |
| `rector/rector` installed             | ✅ Done    | `^2.5.7` in composer.json  |
| `driftingly/rector-laravel` installed | ✅ Done    | `^2.5` in composer.json    |
| `mrpunyapal/rector-pest` installed    | ✅ Done    | `^0.2.17` in composer.json |
| `rector/type-perfect` installed       | ✅ Done    | `^2.1.4` in composer.json  |
| `rector.php` config file              | ❌ Missing | Not created                |
| Rector composer script                | ❌ Missing | Not defined                |
| Rector in CI or RC gate               | ❌ Missing | Not integrated             |

**Verdict: ❌ All packages installed, zero configuration. Rector not usable.**

---

### 3.4. ❌ 3.4 Mago — UNCONFIGURED

| Requirement                        | Status     | Evidence                                   |
| ---------------------------------- | ---------- | ------------------------------------------ |
| `carthage-software/mago` installed | ✅ Done    | `^1.45` in composer.json                   |
| Mago configuration file            | ❌ Missing | No `.mago*` or `mago.*` config files found |
| Mago composer script               | ❌ Missing | Not defined                                |
| Mago in CI or RC gate              | ❌ Missing | Not integrated                             |

**Verdict: ❌ Package installed, zero configuration. Mago not usable.**

---

### 3.5. ⚠️ 3.5 CI Pipeline (#11, #39)

| Requirement                                | Status          | Evidence                                              |
| ------------------------------------------ | --------------- | ----------------------------------------------------- |
| Linux x86_64 CI on every PR                | ✅ Done         | GitHub Actions workflow `.github/workflows/tests.yml` |
| Composer `ci:check` script                 | ✅ Done         | = lint + types + test                                 |
| `pgvector/pgvector:pg18` service container | ❌ Missing      | Not configured in workflow                            |
| `setup-php` with `coverage: pcov`          | ❌ Disabled     | Uses `coverage: none`                                 |
| Dual test-suite (SQLite + Postgres)        | ❌ Missing      | CI only runs one pass on Herd's pg                    |
| 80% line coverage floor                    | ❌ Missing      | No `--coverage --min=80` in test script               |
| Dependabot configured                      | ✅ Done         | 3 ecosystems, weekly grouped                          |
| Branch protection rules                    | ❌ Not verified | Not confirmed as configured                           |

**Verdict: ⚠️ Basic CI exists but lacks pgvector service, coverage enforcement, and dual test-suite needed for #11's Two-Environment Operational Gate.**

---

### 3.6. ❌ 3.6 CONTEXT.md SQLite Terminology Cleanup

| Term                             | Status           |
| -------------------------------- | ---------------- |
| sqlite-vec references            | ❌ Still present |
| FTS5 references                  | ❌ Still present |
| vec0 virtual table references    | ❌ Still present |
| Extension Connection Gate        | ❌ Still present |
| Native Extension Fault           | ❌ Still present |
| All 14 #12 SQLite-specific terms | ❌ Still present |

**Verdict: ❌ Pinned as Postgres-pivot follow-up (#40, #41, #42 graduated). CONTEXT.md retains the SQLite-era glossary.**

---

### 3.7. ❌ 3.7 Domain Structure — Product Files Outside `app/Domain/`

The wayfinder #1/#15 architecture defines each product domain as a self-contained unit. Currently, product-specific code is **split across two directory trees**, violating the `app/Domain/{Product}/` convention:

| File                               | Current Location                             | Correct Location                                    | Status            |
| ---------------------------------- | -------------------------------------------- | --------------------------------------------------- | ----------------- |
| Chinook model stub (Spatie stub)   | `app/Models/Chinook/Chinook.php`             | `app/Domain/Chinook/Models/Chinook.php`             | ❌ Wrong location |
| Northwind model stub (Spatie stub) | `app/Models/Northwind/Northwind.php`         | `app/Domain/Northwind/Models/Northwind.php`         | ❌ Wrong location |
| Pagila model stub                  | ❌ Missing entirely                          | `app/Domain/Pagila/Models/Pagila.php`               | ❌ Missing        |
| Chinook policy                     | `app/Policies/Chinook/ChinookPolicy.php`     | `app/Domain/Chinook/Policies/ChinookPolicy.php`     | ❌ Wrong location |
| Northwind policy                   | `app/Policies/Northwind/NorthwindPolicy.php` | `app/Domain/Northwind/Policies/NorthwindPolicy.php` | ❌ Wrong location |
| Pagila policy                      | ❌ Missing entirely                          | `app/Domain/Pagila/Policies/PagilaPolicy.php`       | ❌ Missing        |

Additionally, Shield's `config/filament-shield.php` policies path is set to `app_path('Policies')` — this must be updated when policies move to `app/Domain/{Product}/Policies/`.

**Verdict: ❌ 4 files in wrong locations + 2 missing files. All product-specific model stubs and policies must be relocated under `app/Domain/{Product}/`. Arch rules must be updated to enforce the convention.**

---

### 3.8. ❌ 3.8 ADR Documentation — Recovery Required

The Wayfinder #15 map resolved >40 decisions across 12 decision clusters. Only 5 of ~17 required ADRs exist (see §2.12). Each resolved wayfinder ticket that represents an architectural decision should have a corresponding ADR documenting the context, decision, and consequences.

**Verdict: ❌ 12 ADRs must be recovered from the Wayfinder #15 decision resolutions. This is the highest-priority documentation gap — without ADRs, architectural decisions are captured only in GitHub issues, which are not part of the codebase documentation lifecycle.**

---

## 4. Gap Summary (Prioritised)

| Priority | Gap                                                                                          | Affected Decision | Impact                                                                                             |
| -------- | -------------------------------------------------------------------------------------------- | ----------------- | -------------------------------------------------------------------------------------------------- |
| **P0**   | **12 of ~17 ADRs missing** — decisions exist only in GitHub issues                           | #38, #15          | Architectural decisions unrecorded in codebase; recovery becomes harder with time                  |
| **P0**   | `phpstan-baseline.neon` with ~150 uncited errors violates #18                                | #18               | Static analysis gate is not clean; PRs may pass while known errors accumulate                      |
| **P0**   | No coverage enforcement (CI has `coverage: none`)                                            | #11, #17          | Cannot verify 80% line coverage floor; RC-gate evidence missing                                    |
| **P0**   | No pgvector service container in CI                                                          | #11, #42          | Postgres extension tests cannot run in CI; Two-Environment gate fails                              |
| **P1**   | **Domain structure violation** — 4 product files outside `app/Domain/`, PagilaPolicy missing | #26, #5           | Product code scattered across app/Models/ + app/Policies/; arch rules don't match directory layout |
| **P1**   | No mutation testing configured                                                               | #17               | RC-gate mutation pass aspirational; cannot detect untested code paths                              |
| **P1**   | 7 of 17+ arch rules missing                                                                  | #17               | Service ownership, presentation isolation, import isolation not enforced                           |
| **P1**   | Northwind Filament resources missing                                                         | #16, #30          | One of three products has no CRUD UI                                                               |
| **P1**   | PagilaPolicy missing (compounds domain structure issue)                                      | #26               | Authorization gap for Pagila panel                                                                 |
| **P2**   | Rector not configured (packages installed)                                                   | #17               | RC-gate rector lint cannot run; code quality drift unchecked                                       |
| **P2**   | Mago not configured (package installed)                                                      | #17               | RC-gate mago arch depth cannot run                                                                 |
| **P2**   | Team Artefacts (#36) entirely unimplemented                                                  | #36               | Saved Search and Team Dashboard features absent                                                    |
| **P2**   | Implementation-Readiness Dossier (#37) entirely unimplemented                                | #37               | No stage evidence tracking; manual operator verification required                                  |
| **P2**   | `product_portfolio_snapshots` view migration missing                                         | #35               | Portfolio card uses live queries, no snapshot isolation                                            |
| **P2**   | EmbeddedJob uses raw HTTP instead of `laravel/ai` SDK                                        | #33               | Inconsistent with architectural decision; bypasses AI SDK abstraction                              |
| **P3**   | CONTEXT.md still references SQLite terms                                                     | #40, #41, #42     | Domain glossary stale; may confuse new developers                                                  |
| **P3**   | ADRs at wrong path (`docs/adr/` not `docs/10-architecture/1015-adrs/`)                       | #38               | Path non-compliance with documentation lifecycle                                                   |
| **P3**   | macOS Herd `--threads=1` PHPStan quirk not documented                                        | #18               | Developer friction on macOS (silent exit 1)                                                        |
| **P3**   | Only 1 unit test exists                                                                      | #17               | Minimal unit coverage outside feature tests                                                        |
| **P3**   | No `test:*` dedicated composer scripts                                                       | #17               | Must run full suite even for targeted testing                                                      |

---

## 5. Row Counts

| Metric                                 | Value                                   |
| -------------------------------------- | --------------------------------------- |
| Decision clusters reviewed             | 12                                      |
| Fully compliant                        | 4                                       |
| Partially compliant                    | 3                                       |
| Missing/not implemented                | 5 (inc. ADR recovery, Domain structure) |
| Arch rules implemented / specified     | 10 of 17+                               |
| Feature tests                          | 35                                      |
| Unit tests                             | 1                                       |
| Total tests (excluding CI/tool config) | 36 test files                           |
| Baselined PHPStan errors               | ~150                                    |
| ADRs written / required                | 5 of ~17                                |
| Product files outside `app/Domain/`    | 4 mislocated (+ 2 missing)              |
| Composer scripts for quality           | 3 (`test`, `ci:check`, `types:check`)   |
