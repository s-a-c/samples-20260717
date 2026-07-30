---
title: "Wayfinder #15 Compliance Report (Re-audit)"
description: "> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)"
type: report
tags: \[report, specs, wayfinder, "15"]
updated: 2026-07-30
---

# Wayfinder #15 Compliance Report (Re-audit)

> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)
> **Prior report:** [`2026-07-25-wayfinder-15-compliance-report.md`](./2026-07-25-wayfinder-15-compliance-report.md)
> **Remediation epic:** [#49 — Wayfinder #15 Gap Remediation](https://github.com/s-a-c/samples-20260717/issues/49) (all 17 child tasks CLOSED)
> **Destination:** Production-ready Laravel 13 + PHP 8.5 application with Chinook, Northwind, and Pagila as three sample products
> **Date:** 2026-07-28
> **Status:** Substantially compliant — the 2026-07-25 gaps are closed; 7 follow-up gaps remain, concentrated in static-analysis discipline, CI gate fidelity, and one search-pipeline failure contract

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Executive Summary](#1-executive-summary)
- [2. Delta vs. 2026-07-25 Report (Remediation Verified)](#2-delta-vs-2026-07-25-report-remediation-verified)
- [3. Decision Cluster Compliance (Current State)](#3-decision-cluster-compliance-current-state)
- [4. Remaining Gaps](#4-remaining-gaps)
- [5. Gap Summary (Prioritised)](#5-gap-summary-prioritised)
- [6. Row Counts](#6-row-counts)

</details>

---

## 1. Executive Summary

The Gap Remediation epic (#49) closed all 17 child tasks. This re-audit verifies the closures against the codebase and surfaces a second wave of gaps that the first report did not catch (or that regressed after remediation).

**Headline:** the application architecture is **fully implemented** across all 12 decision clusters. The remaining gaps are in **discipline and enforcement** — the static-analysis baseline regrew uncited, the CI workflow does not actually run the full quality gate, and one search failure contract is silently violated — plus two **documentation-completeness** items (dossier content, three foundational ADRs).

**Compliance by tier (re-audit):**

| Tier                         | Description                       | Count               |
| ---------------------------- | --------------------------------- | ------------------- |
| ✅ Fully Compliant           | Implemented and verified per spec | 9 decision clusters |
| ⚠️ Partially Compliant       | Implemented with a contract gap   | 3 decision clusters |
| ❌ Missing / Not Implemented | Not built                         | 0 decision clusters |

No decision cluster is _unimplemented_. The ⚠️ ratings are narrow contract/enforcement deviations, not missing features.

---

## 2. Delta vs. 2026-07-25 Report (Remediation Verified)

Every gap flagged on 2026-07-25 was addressed. Evidence:

| 2026-07-25 gap (priority)                            | Status           | Evidence (2026-07-28)                                                                                                                                                                          |
| ---------------------------------------------------- | ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 12 of ~17 ADRs missing (P0)                          | ✅ Fixed         | 18 ADRs at `docs/10-architecture/1003-adr/`; all 12 previously-missing decisions now documented                                                                                                |
| ADRs at wrong path (P3)                              | ✅ Fixed         | Standardised on `docs/10-architecture/1003-adr/` (matches `AGENTS.md` §5.1); old `docs/adr/` removed                                                                                           |
| `phpstan-baseline.neon` uncited (P0) — _see §4.G1_   | ⚠️ **Regressed** | Annotated in #46, then **regenerated 2026-07-27 losing all citations**; 242 bare entries remain                                                                                                |
| No coverage enforcement (P0)                         | ⚠️ Partial       | `composer test:coverage` (`artisan test --min=80`) correct; **CI bypasses it** — see §4.G2                                                                                                     |
| No pgvector service in CI (P0)                       | ✅ Fixed         | `pgvector/pgvector:pg18` service container in `.github/workflows/tests.yml`                                                                                                                    |
| Domain structure — files outside `app/Domain/` (P1)  | ✅ Resolved      | Convention is `App\Models\{Product}` (Laravel-native); enforced by 3 `arch()` namespace rules. The "move to `app/Domain/`" premise was withdrawn — `App\Models\{Product}` is the agreed layout |
| PagilaPolicy missing (P1)                            | ✅ Fixed         | `app/Policies/PagilaPolicy.php` present                                                                                                                                                        |
| 7 of 17+ arch rules missing (P1)                     | ✅ Fixed         | 24 `arch()` rules now in `tests/Architecture/ArchitectureTest.php` (spec floor was 15+)                                                                                                        |
| Northwind Filament resources missing (P1)            | ✅ Fixed         | 7 resources under `app/Filament/Northwind/Resources/`                                                                                                                                          |
| Rector unconfigured (P2)                             | ✅ Fixed         | `rector.php` with prepared sets; `composer rector` / `rector:fix` scripts                                                                                                                      |
| Mago unconfigured (P2)                               | ✅ Fixed         | `mago.toml` + `.mago/`; `composer mago:analyze` / `mago:guard` scripts                                                                                                                         |
| Mutation testing unconfigured (P1)                   | ✅ Fixed         | `infection.json.dist` (`minMsi: 50`, `minCoveredMsi: 60`); `composer test:mutation`                                                                                                            |
| Team Artefacts unimplemented (P2)                    | ✅ Fixed         | `2026_07_26_214832_create_team_artefacts_table.php` + `app/Models/TeamArtefact.php`                                                                                                            |
| `product_portfolio_snapshots` view missing (P2)      | ✅ Fixed         | `2026_07_24_213000_create_product_portfolio_snapshots_view.php`                                                                                                                                |
| EmbeddingJob used raw HTTP not `laravel/ai` SDK (P2) | ✅ Fixed*        | Now uses `Laravel\Ai\Embeddings`. *Failure contract still broken — see §4.G5                                                                                                                   |
| CONTEXT.md SQLite terminology (P3)                   | ✅ Fixed         | Zero SQLite-era terms remain (`sqlite-vec`, `FTS5`, `vec0`, Extension Gate, etc. all removed)                                                                                                  |
| macOS Herd PHPStan quirk undocumented (P3)           | ✅ Fixed         | `README.md` §8.1–8.2 documents Xdebug/parallelism behaviour                                                                                                                                    |
| Only 1 unit test (P3)                                | ✅ Fixed         | 4 unit tests: `ReciprocalRankFusionTest`, `ResetEvidenceTest`, `SamplesProductTest`, `ExampleTest`                                                                                             |
| No `test:*` composer scripts (P3)                    | ✅ Fixed         | `test:unit`/`feature`/`pg`/`arch`/`coverage`/`mutation`/`type-cov`/`livewire` all present                                                                                                      |

---

## 3. Decision Cluster Compliance (Current State)

> Legend: ✅ fully compliant · ⚠️ implemented with a narrow contract/enforcement gap · ❌ missing.

### 3.1. ✅ PostgreSQL Pivot (#40, #41, #42)

Extensions, per-product schemas, `#[Table]` attributes, `vector(1024)`, HNSW + GIN, `test_postgres_extensions_are_installed`, `pgsql:check` — all present and correct. **Fully compliant.**

### 3.2. ✅ UUIDv7 Strategy (#24)

Native `HasUuids`, native `uuid` columns, UUIDv7 PKs on all app-owned tables, explicit-id-on-reuse in `SourceIdentityRegistry`. **Fully compliant.**

### 3.3. ✅ Source Identity Registry (#25)

Shared `public.source_identities`, JSONB `source_key`, `GENERATED ALWAYS AS … STORED` `product`, no FKs, `UNIQUE (entity, source_key)`. **Fully compliant.**

### 3.4. ✅ Product Import Pipeline (#28)

Unified pipeline, shadow-schema staging, per-product importers + readers, `reset_runs` 5-state machine, advisory locks, pin manifests, source cache. **Fully compliant.**

### 3.5. ✅ Product Reset Semantics (#29)

`ResetWindow`, `BelongsToProductDomain` trait, `ProductResetWindowOpen` (HTTP 423), `ResetEvidence` VO (`SCHEMA_VERSION = 1`), `ResetConfirmationService`, `product:confirm`/`abort`/`recover`/`status`. **Fully compliant.**

### 3.6. ✅ Filament Panels + Resources (#16, #30)

4 panel providers, no tenancy, Fortify owns auth, per-panel discovery roots, Shield in Admin only, `operator:create`, all three product resource sets present (Chinook 8, Northwind 7, Pagila 10). **Fully compliant.** _(#30's resource-generation decision itself lacks a dedicated ADR — see §4.G6, documentation only.)_

### 3.7. ✅ Spatie + Shield + Fortify Coexistence (#26)

Spatie sole RBAC engine, teams mode off, role taxonomy (`super_admin` + `{product}_curator`), 4-panel redirect to Fortify `/login`, `operator:create` + `ProvisionOperator`. **Fully compliant.**

### 3.8. ⚠️ Search Domain (#31, #32, #33, #34)

Projections, `document_tsv GENERATED`, `embedding_state` lifecycle, triggers, `Tier1SourceObserver`, `FederatedSearchService`, `ReciprocalRankFusion`, `SearchDeepLinkRegistry`, `laravel/ai` SDK — all present. **Gap: `EmbeddingJob` violates the #33 failure contract** (swallows all errors → fake zero-vector → marks `complete`; no backoff, never `failed`). See §4.G5.

### 3.9. ✅ Portfolio Card (#35)

`ProductPortfolioCard` widget, `Portfolio` page, 3-column layout, registered 3×, `product_portfolio_snapshots` view. **Fully compliant.**

### 3.10. ✅ Team Artefacts (#36)

`team_artefacts` migration (polymorphic `type`, UUIDv7, `created_by` SET NULL) + `TeamArtefact` model. **Fully compliant.** _(Saved Search / Team Dashboard UX wiring is execution work beyond the #36 schema decision.)_

### 3.11. ⚠️ Implementation-Readiness Dossier (#37)

`dossier:generate` command + `151501-contents.md` + `151502-stage-template.md` exist. **Gap: only scaffolding is emitted** — no stage files `151503+` are generated from #11 stages / wayfinder metadata; every stage status is `_pending_`. The dossier is not yet a filled operational record. See §4.G4.

### 3.12. ⚠️ Architectural Decision Records (#38)

18 ADRs at the correct path; all wayfinder #15 decisions documented. **Gap: three foundational map-#1 decisions lack ADRs** — #30 (resource generation), #11 (verification/acceptance/Two-Environment gate), #13 (auth/audit/dashboard boundary). See §4.G6.

---

## 4. Remaining Gaps

### G1 — PHPStan baseline regressed to 242 uncited entries (P0)

| Aspect                         | Finding                                                                                                                                            |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| File                           | `phpstan-baseline.neon` (1531 lines, 242 `message:` entries, modified 2026-07-27)                                                                  |
| Policy                         | #18 = "`level: max`, **no baseline, no ratchet**"; #15 resolution = "shrinking list with **per-entry ticket citations**"                           |
| #46 ("T17") claimed resolution | "Annotated 150+ phpstan-baseline entries with ticket citations"                                                                                    |
| Current reality                | **Zero** per-entry citation comments survive; `phpstan.neon` still `include`s the baseline                                                         |
| Likely cause                   | Baseline regenerated (2026-07-27) _after_ #46 closed, discarding the annotations                                                                   |
| Impact                         | Static-analysis gate is not clean; PRs pass while 242 known violations sit frozen; #18's terminal-state goal blocked; no guard prevents recurrence |

**Verdict: ❌ Non-compliant with #18 and #46. Highest-priority gap.**

---

### G2 — CI workflow does not enforce the full PR gate (P1)

The workflow `.github/workflows/tests.yml` runs a **single** `vendor/bin/phpunit` step. It does **not** run Pint, PHPStan, Mago, Rector, or the Architecture suite, and the 80 % coverage floor is not reliably enforced.

| Requirement (#17 PR-gate)        | CI status     | Detail                                                                                                                                                                                                                                                                                          |
| -------------------------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Pint (formatting)                | ❌ Not run    | Only `phpunit` invoked                                                                                                                                                                                                                                                                          |
| Larastan/PHPStan (`level: max`)  | ❌ Not run    |                                                                                                                                                                                                                                                                                                 |
| Mago / Architecture suite        | ❌ Not run    | `composer test:arch` not called                                                                                                                                                                                                                                                                 |
| Unit + Feature suites            | ✅ Run        | Via phpunit                                                                                                                                                                                                                                                                                     |
| `pgvector/pgvector:pg18` service | ✅ Present    |                                                                                                                                                                                                                                                                                                 |
| 80 % line-coverage floor         | ⚠️ Unreliable | Step uses `vendor/bin/phpunit … --coverage-min=80`; **`--coverage-min` is not a valid PHPUnit 12 option** (confirmed via `phpunit --help`). Correct mechanism: `<coverage min="80">` in `phpunit.xml`, or `php artisan test --coverage --min=80`. `phpunit.xml` has no `<coverage min>` element |
| Invokes `composer ci:check`      | ❌ No         | `ci:check` _does_ chain lint+types+test, but CI calls raw phpunit instead                                                                                                                                                                                                                       |

**Verdict: ❌ CI enforces only "tests pass". The P0/P1 quality gate from #17/#18 is not on the PR path.**

---

### G3 — Dual test suite not realised (P2)

#17 specified a **dual suite**: default `tests/Feature/*` on SQLite `:memory:`, and `tests/Feature/Postgres/*` on the pgvector service. Current state: `phpunit.xml` sets `DB_CONNECTION=pgsql` globally and CI runs **everything** on Postgres. The `test:pg` script exists but the SQLite tier is effectively absent — there is one Postgres-only suite, not two.

**Verdict: ⚠️ Deviation from #17's dual-suite design.** Either restore the SQLite default + Postgres sub-suite, or amend #17 with an ADR ratifying a single Postgres-only suite (the simpler, defensible choice — see plan Task G3).

---

### G4 — Implementation-Readiness Dossier is a skeleton, not a record (P2)

`DossierGenerate` writes only `151501-contents.md` + `151502-stage-template.md`. The #37 decision called for stage files generated from **#11 stages + Wayfinder #15 child-issue metadata + `acceptance_criteria`** with `> **OPERATOR TODO:**` / `> **EVIDENCE TODO:**` markers. No `151503`–`151517` stage files exist; all four stage rows read `_pending_`.

**Verdict: ⚠️ Tooling scaffolded; the version-controlled operational record is empty.**

---

### G5 — `EmbeddingJob` breaks the #33 failure contract (P2)

`app/Jobs/EmbeddingJob.php` uses `Laravel\Ai\Embeddings` ✅, but:

| #33 requirement                              | Current behaviour                                                                                                 |
| -------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| 3 retries with **exponential backoff**       | `$tries = 3` only — **no `$backoff`**                                                                             |
| On exhaustion → `embedding_state = 'failed'` | **Never reached**: `try/catch` swallows every `Throwable`                                                         |
| "No infinite retry"                          | Worse than infinite — silently "succeeds"                                                                         |
| Fallback                                     | Substitutes a **placeholder zero-vector** (`array_fill(0, 1024, 0.01)`) and writes `embedding_state = 'complete'` |

**Impact:** semantic-index rows are silently poisoned with fake vectors whenever the AI provider is unavailable; operators cannot distinguish real from synthetic embeddings; the #32 Baseline Invariant (`embedding_state NOT IN ('complete','lexical_only')` = 0) passes vacuously.

**Verdict: ❌ Non-compliant with #33's failure path. The `laravel/ai` migration is complete; the failure semantics are not.**

---

### G6 — Three foundational decisions lack ADRs (P3)

All Wayfinder #15 decisions are documented. Three **map-#1** decisions that #15 builds on have no ADR:

| Decision | Title                                                                  | Covered indirectly?                                    |
| -------- | ---------------------------------------------------------------------- | ------------------------------------------------------ |
| #30      | Filament Resource generation strategy                                  | Mentioned in panel-isolation ADR, no standalone record |
| #11      | Verification, delivery & operational acceptance (Two-Environment gate) | Referenced in dossier/plan docs only                   |
| #13      | Authorization, audit & configurable-dashboard package boundary         | Folded into Spatie/Shield/Fortify ADR narrative        |

**Verdict: ⚠️ Documentation-completeness gap. No architectural risk; decisions are honoured in code.**

---

### G7 — No guard against orphan/uncited `ignoreErrors` (P3)

#17 / T8.3 called for an architecture test that **fails the build on orphan (uncited) `ignoreErrors`** entries. No such guard exists — which is exactly why G1 regressed undetected. This is the enforcement backstop for G1.

**Verdict: ⚠️ Missing guard. Cheap to add; prevents recurrence of G1.**

---

## 5. Gap Summary (Prioritised)

| Priority | Gap                                                                | Cluster / Decision | Remediation owner       |
| -------- | ------------------------------------------------------------------ | ------------------ | ----------------------- |
| **P0**   | G1 — `phpstan-baseline.neon`: 242 uncited entries, contradicts #18 | #18, #17           | Plan Task G1            |
| **P1**   | G2 — CI runs only phpunit; no Pint/PHPStan/Mago/arch/coverage gate | #17, #18, #39      | Plan Task G2            |
| **P2**   | G5 — `EmbeddingJob` swallows errors → fake vectors, never `failed` | #33                | Plan Task G5            |
| **P2**   | G3 — Dual test suite absent (Postgres-only)                        | #17                | Plan Task G3 (decision) |
| **P2**   | G4 — Dossier emits only scaffolding; stages unfilled               | #37                | Plan Task G4            |
| **P3**   | G6 — ADRs missing for #30, #11, #13                                | #38                | Plan Task G6            |
| **P3**   | G7 — No arch guard for orphan `ignoreErrors` citations             | #17, #18           | Plan Task G7            |

---

## 6. Row Counts

| Metric                                           | 2026-07-25 | 2026-07-28            |
| ------------------------------------------------ | ---------- | --------------------- |
| Decision clusters fully compliant                | 4          | 9                     |
| Decision clusters partially compliant            | 3          | 3                     |
| Decision clusters missing                        | 5          | 0                     |
| ADRs written                                     | 5          | 18                    |
| `arch()` rules                                   | 10         | 24                    |
| Unit test files                                  | 1          | 4                     |
| Feature test files                               | 35         | 36                    |
| Baselined PHPStan errors                         | ~150       | 242 (uncited)         |
| Product Filament resource sets                   | 2 of 3     | 3 of 3                |
| Composer `test:*` scripts                        | 0          | 8                     |
| Quality tools configured (Rector/Mago/Infection) | 0 of 3     | 3 of 3                |
| Open Wayfinder #15 child tasks                   | —          | 1 (`#50` T8 tracking) |
| Remaining gaps                                   | 10         | 7                     |
