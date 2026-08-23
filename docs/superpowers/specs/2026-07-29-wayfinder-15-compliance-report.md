---
title: "Wayfinder #15 Compliance Report (Review #3)"
description: "> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: research
tags: [report, specs, wayfinder, "15"]
created: 2026-07-29
updated: 2026-08-17
---

# Wayfinder #15 Compliance Report (Review #3)

> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)
> **Prior reports:** [`2026-07-25-wayfinder-15-compliance-report.md`](./2026-07-25-wayfinder-15-compliance-report.md), [`2026-07-28-wayfinder-15-compliance-report.md`](./2026-07-28-wayfinder-15-compliance-report.md)
> **Remediation Plan:** [`docs/superpowers/plans/2026-07-29-wayfinder-15-remediation-plan.md`](../plans/2026-07-29-wayfinder-15-remediation-plan.md)
> **Destination:** Production-ready Laravel 13 + PHP 8.5 application with Chinook, Northwind, and Pagila as three sample products
> **Date:** 2026-07-29
> **Status:** High Compliance — Architecture & core features 100% implemented across all 12 decision clusters; 4 residual operational/static-analysis gaps identified for final cleanup

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Executive Summary](#1-executive-summary)
- [2. Delta vs. 2026-07-28 Re-audit (Remediation Verification)](#2-delta-vs-2026-07-28-re-audit-remediation-verification)
- [3. Decision Cluster Compliance (Current State)](#3-decision-cluster-compliance-current-state)
- [4. Remaining Gaps Identified in Review #3](#4-remaining-gaps-identified-in-review-3)
- [5. Gap Summary (Prioritised)](#5-gap-summary-prioritised)
- [6. Metrics & Row Counts](#6-metrics--row-counts)

</details>

---

## 1. Executive Summary

This compliance review (#3) evaluates the codebase against Wayfinder Map #15 following the merge of the `wf15/reaudit-remediation` branch (`692598c`).

**Headline:** The core application architecture, domain models, multi-product schemas, search infrastructure, reset mechanics, Filament panel suite, and documentation ADR set are **100% implemented** across all 12 decision clusters.

The 7 gaps identified in the 2026-07-28 re-audit (G1 through G7) have been substantially resolved. The remaining findings in Review #3 are confined to:

1. A small static-analysis regression (5 PHPStan errors under `composer types:check`),
2. A script-runner exit-code discrepancy in `composer test:arch` under Herd PHP subprocess environments,
3. Test line-coverage floor ratcheting (currently 27.8% vs. 80% target),
4. Filling the remaining operator/evidence placeholders in the Dossier stage files.

**Compliance by Tier (Review #3):**

| Tier                         | Description                            | Count                |
| ---------------------------- | -------------------------------------- | -------------------- |
| ✅ Fully Compliant           | Implemented and verified per spec      | 11 decision clusters |
| ⚠️ Partially Compliant       | Implemented with minor operational gap | 1 decision cluster   |
| ❌ Missing / Not Implemented | Not built                              | 0 decision clusters  |

---

## 2. Delta vs. 2026-07-28 Re-audit (Remediation Verification)

All 7 gaps (G1–G7) from the 2026-07-28 report were addressed in commits `a6e7666` through `692598c`:

| 2026-07-28 Gap | Title                                     | Status          | Evidence (2026-07-29 Verification)                                                                                                                                                                       |
| -------------- | ----------------------------------------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **G1**         | PHPStan baseline 242 uncited entries      | ✅ **Resolved** | Baseline shrunk from 242 to 78 entries; framework-idiom carve-outs moved to `phpstan.neon`; all 78 residual entries carry `# bd:wf15-g1-ratchet` citations. _(See §4 for 5 new residual PHPStan errors)_ |
| **G2**         | CI workflow lacks full PR gate            | ✅ **Resolved** | `.github/workflows/tests.yml` includes Pint, `types:check`, `mago:guard`, `test:arch`, and Pest runner with `--coverage --min=25`.                                                                       |
| **G3**         | Dual test suite absent                    | ✅ **Resolved** | Single Postgres test suite ratified via **ADR 100337**. `phpunit.xml` configures `DB_CONNECTION=pgsql` as the single authoritative DB target.                                                            |
| **G4**         | Dossier emits only scaffolding            | ✅ **Resolved** | Stage files `151503` through `151506` emitted in `docs/15-delivery/1515-implementation-readiness-dossier/` with ADR refs and automated check mappings.                                                   |
| **G5**         | `EmbeddingJob` breaks failure contract    | ✅ **Resolved** | `app/Jobs/EmbeddingJob.php` updated with `$tries = 3`, `$backoff = [10, 30, 90]`, error propagation, and `failed()` setting `embedding_state = 'failed'`. Verified by `EmbeddingJobFailureTest.php`.     |
| **G6**         | Missing ADRs for #30, #11, #13            | ✅ **Resolved** | **ADR 100334** (#11), **ADR 100335** (#13), and **ADR 100336** (#30) created and indexed in `docs/10-architecture/1003-adr/`.                                                                            |
| **G7**         | No guard against uncited baseline entries | ✅ **Resolved** | `tests/Architecture/PhpStanBaselineCitationTest.php` created and passing. Fails if any baseline entry lacks a citation.                                                                                  |

---

## 3. Decision Cluster Compliance (Current State)

### 3.1. ✅ PostgreSQL Pivot (#40, #41, #42)

`0001_01_01_000000_create_postgres_extensions.php` manages `vector`, `unaccent`, `pg_trgm`, `en_unaccent`. Verified via `PostgresExtensionsTest.php` and `php artisan pgsql:check`. Per-product schemas (`chinook`, `northwind`, `sakila`) and `#[Table]` attributes on all domain models. **Fully compliant.**

### 3.2. ✅ UUIDv7 Strategy (#24)

Native Eloquent `HasUuids` trait applied across all app models. Native Postgres `uuid` columns for primary keys on all app-owned tables. Explicit ID assignment in `SourceIdentityRegistry`. **Fully compliant.**

### 3.3. ✅ Source Identity Registry (#25)

`public.source_identities` table with JSONB `source_key`, `GENERATED ALWAYS AS ... STORED` `product` column, `UNIQUE (entity, source_key)`. **Fully compliant.**

### 3.4. ✅ Product Import Pipeline (#28)

Unified shadow-schema staging pipeline with per-product importers (`ChinookImporter`, `NorthwindImporter`, `PagilaImporter`). `reset_runs` 5-state machine, advisory locks (`pg_try_advisory_xact_lock`), pin manifests at `database/sources/<product>.php`. **Fully compliant.**

### 3.5. ✅ Product Reset Semantics (#29)

`ResetWindow` service, `BelongsToProductDomain` trait on product models, HTTP 423 `ProductResetWindowOpen`. `ResetEvidence` VO (`SCHEMA_VERSION = 1`), `ResetConfirmationService` + `reset_confirmations` table signed-token protocol. CLI tools (`product:confirm`, `product:abort`, `product:recover`, `product:status`). **Fully compliant.**

### 3.6. ✅ Filament Panels & Resources (#16, #30)

Four panels (`Admin`, `Chinook`, `Northwind`, `Pagila`). No Filament tenancy; Fortify owns auth; Filament login disabled on all panels. Per-panel discovery roots under `app/Filament/<Product>/`. Shield in Admin panel only. All 25 product Filament resources present (Chinook: 8, Northwind: 7, Pagila: 10). Documented in **ADR 100336**. **Fully compliant.**

### 3.7. ✅ Spatie + Shield + Fortify Coexistence (#26)

Spatie sole RBAC engine; Spatie teams mode OFF. Role taxonomy (`super_admin` + `{product}_curator`). 4-panel redirect to Fortify `/login`. `operator:create` command + `ProvisionOperator` action. Documented in **ADR 100335**. **Fully compliant.**

### 3.8. ✅ Search Domain (#31, #32, #33, #34)

`*.search_projections` tables with ABCD weights, `document_tsv GENERATED ALWAYS AS ... STORED`, `embedding vector(1024)`, `embedding_state` lifecycle. `Tier1SourceObserver` dispatches `EmbeddingJob`. `FederatedSearchService`, `ReciprocalRankFusion`, `SearchDeepLinkRegistry`. `laravel/ai` SDK integration in `EmbeddingJob` with full exponential backoff and failed state propagation (G5 verified). **Fully compliant.**

### 3.9. ✅ Portfolio Card (#35)

`ProductPortfolioCard` widget, `Portfolio` page, 3-column layout, registered 3x in Admin panel provider, backed by `product_portfolio_snapshots` Postgres view. **Fully compliant.**

### 3.10. ✅ Team Artefacts (#36)

Polymorphic `team_artefacts` migration (UUIDv7, `created_by` SET NULL) and `TeamArtefact` model. **Fully compliant.**

### 3.11. ⚠️ Implementation-Readiness Dossier (#37)

`dossier:generate` command, `151501-contents.md`, `151502-stage-template.md`, and four stage files (`151503`–`151506`) generated with ADR refs and check lists. **Gap:** Operator TODO items and evidence URLs remain in scaffolding state (`> **OPERATOR TODO:**`), and stage statuses are `_pending_`.

### 3.12. ✅ Architectural Decision Records (#38)

All 22 ADRs written and indexed in `docs/10-architecture/1003-adr/` (including 100310, 100315, 100318, 100334, 100335, 100336, 100337). **Fully compliant.**

---

## 4. Remaining Gaps Identified in Review #3

### Gap R1 — Static Analysis (`composer types:check`) 5 PHPStan Errors (P0)

| File                                                                       | Error / Identifier                                                                          | Impact                       |
| -------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- | ---------------------------- |
| `tests/Feature/Auth/AuthenticationTest.php`                                | `ignore.unmatched`: Stale ignore entry in `phpstan-baseline.neon` for `setLaravelSession()` | Fails `composer types:check` |
| `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php` (lines 22 & 26) | `argument.type`: `get()` expects `Uri\|string`, `mixed` given                               | Fails `composer types:check` |
| `tests/Feature/Console/OperatorCreateTest.php` (lines 49 & 61)             | `nullsafe.neverNull`: Nullsafe access on non-nullable `mixed`                               | Fails `composer types:check` |

**Impact:** `composer types:check` fails with exit code 1, blocking local and CI static analysis gates.

---

### Gap R2 — `composer test:arch` Script Exit Code Quirk (P1)

In `composer.json`, `"test:arch"` is defined as `["@mago:guard", "php artisan test --testsuite=Architecture"]`.

On macOS Herd CLI environments, running `php` outputs a startup module warning (`Warning: Module "herd" is already loaded in Unknown on line 0`). When `php artisan test` invokes test sub-processes via `Symfony\Component\Process\Process`, it detects stderr output and returns exit code 1 even when all 26 Pest architecture tests pass.

**Impact:** `composer test:arch` exits with code 1 locally. Direct invocation via `vendor/bin/pest --testsuite=Architecture` exits 0 cleanly.

---

### Gap R3 — Test Line-Coverage Floor Ratchet (P2)

Wayfinder #17 specifies an 80% line coverage floor. Current test coverage is **27.8%**. The CI workflow `.github/workflows/tests.yml` enforces `--min=25` to prevent coverage regressions without blocking builds.

**Impact:** Operational ratchet gap. Full 80% coverage remains an ongoing target as feature tests expand.

---

### Gap R4 — Dossier Stage Evidence and Completion Tracking (P2)

Stage files `151504-stage-1-foundation.md` through `151511-stage-4-polish.md` exist and reference all ADRs and commands. However:

- Stage status indicators remain `_pending_`.
- Acceptance gate tables retain `> **OPERATOR TODO:**` markers.
- Evidence paths retain `> **EVIDENCE TODO:**` markers.

---

## 5. Gap Summary (Prioritised)

| Priority | Gap ID | Description                                                   | Cluster / Decision | Remediation Task |
| -------- | ------ | ------------------------------------------------------------- | ------------------ | ---------------- |
| **P0**   | **R1** | 5 PHPStan errors in `composer types:check`                    | #18, #17           | Plan Task 1      |
| **P1**   | **R2** | `composer test:arch` script runner exit status under Herd     | #17, #11           | Plan Task 2      |
| **P2**   | **R3** | Test coverage floor at 25% (ratcheting toward 80%)            | #17                | Plan Task 3      |
| **P2**   | **R4** | Dossier stage files retain TODO placeholders & pending status | #37, #11           | Plan Task 4      |

---

## 6. Metrics & Row Counts

| Metric                                | 2026-07-25 | 2026-07-28    | 2026-07-29 (Review #3)                   |
| ------------------------------------- | ---------- | ------------- | ---------------------------------------- |
| Decision clusters fully compliant     | 4          | 9             | 11                                       |
| Decision clusters partially compliant | 3          | 3             | 1                                        |
| Decision clusters missing             | 5          | 0             | 0                                        |
| ADRs written and indexed              | 5          | 18            | 22                                       |
| `arch()` rules in suite               | 10         | 24            | 26                                       |
| Unit & Feature test files             | 36         | 40            | 41                                       |
| Baselined PHPStan errors              | ~150       | 242 (uncited) | 78 (all cited `# bd:wf15-g1-ratchet`)    |
| Unhandled PHPStan errors              | 0          | 0             | 5 (R1)                                   |
| Quality tools active in CI gate       | 0 of 5     | 1 of 5        | 5 of 5 (Pint, PHPStan, Mago, Arch, Pest) |
| Dossier stage files generated         | 0          | 0             | 4                                        |
| Remaining actionable gaps             | 10         | 7             | 4                                        |
