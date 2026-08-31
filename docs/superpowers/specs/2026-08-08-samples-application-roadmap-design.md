---
title: "Samples Application Delivery Roadmap Design"
description: "Canonical project-wide roadmap design consolidating prior wayfinder decisions, completed work, and remaining delivery gaps."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [spec, roadmap, wayfinder, delivery, samples]
created: 2026-08-08
updated: 2026-08-31
---

# Samples Application Delivery Roadmap Design

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Destination](#2-destination)
- [3. Scope](#3-scope)
    - [3.1. 3.1 In scope](#31-31-in-scope)
    - [3.2. 3.2 Out of scope](#32-32-out-of-scope)
- [4. Architecture](#4-architecture)
- [5. Delivery Phases](#5-delivery-phases)
    - [5.1. Phase 0 — Baseline and governance](#51-phase-0--baseline-and-governance)
    - [5.2. Phase 1 — Runtime and schema safety](#52-phase-1--runtime-and-schema-safety)
    - [5.3. Phase 2 — Import and reset correctness](#53-phase-2--import-and-reset-correctness)
    - [5.4. Phase 3 — Product panels and Admin workflows](#54-phase-3--product-panels-and-admin-workflows)
    - [5.5. Phase 4 — Search and derived read models](#55-phase-4--search-and-derived-read-models)
    - [5.6. Phase 5 — Quality, CI, and documentation](#56-phase-5--quality-ci-and-documentation)
    - [5.7. Phase 6 — Release acceptance and operations](#57-phase-6--release-acceptance-and-operations)
- [6. Completion Semantics](#6-completion-semantics)
- [7. Tracker Model](#7-tracker-model)
- [8. Acceptance Contract](#8-acceptance-contract)
- [9. References](#9-references)

---

</details>

## 1. Purpose

This design defines one canonical, project-wide delivery roadmap for the
Samples application. It consolidates the decisions and execution work recorded
by Wayfinder Maps #1, #15, #49, #64, and #73, while separating tracker closure
from runtime verification and release acceptance.

The roadmap exists because the application currently has a gap between
migration-time correctness and lifecycle correctness: the
`product_portfolio_snapshots` view exists after a fresh migration, but product
schema replacement with `DROP SCHEMA ... CASCADE` removes the dependent view and
Laravel's migration ledger does not recreate it. The same class of gap can
affect search projections, embeddings, reset evidence, and Admin workflows.

## 2. Destination

Reach a release-ready Samples application in which Chinook, Northwind, and
Pagila remain independently importable, resettable, searchable, and
administrable after real data operations. Every accepted phase must have
working code, focused Pest evidence, dossier evidence, operator/recovery
commands, and verification on both Herd macOS arm64 and Linux CI PostgreSQL.

## 3. Scope

### 3.1. 3.1 In scope

- Existing product, panel, schema, identity, import, reset, search, Admin,
  quality, documentation, and acceptance work recorded in prior maps.
- Remaining implementation gaps exposed by comparing those decisions with the
  current code and database lifecycle.
- The full upstream-to-domain import path, shadow-schema publish, dependent
  object recreation, search rebuild, embedding drain, and recovery evidence.
- Northwind resource completion and Admin import/stats lifecycle verification.
- Project-wide CI, dossier, ADR, operator, and two-environment acceptance
  reconciliation.

### 3.2. 3.2 Out of scope

- Customer-facing commerce or a unified business domain across products.
- Separate physical databases or per-team dataset clones.
- Production hosting topology beyond Herd macOS arm64 and Linux x86_64 CI.
- Reopening settled architecture decisions without a recorded material reason.
- A separate OLAP/data-warehouse product.

## 4. Architecture

The roadmap preserves the accepted PostgreSQL multi-schema architecture:

- `public` owns application infrastructure, identities, reset records,
  permissions, teams, and cross-product read models.
- `<product>` owns the live Chinook, Northwind, or Pagila domain schema.
- `<product>_source` holds upstream-shaped scratch data during a transform.
- `<product>_staging` is migration-built app-shaped data with UUID identities,
  constraints, triggers, and search projections.
- Publish is an atomic shadow-schema swap followed by explicit recreation of
  cross-schema public dependents, especially
  `public.product_portfolio_snapshots`.
- Staging writes use models that are explicitly exempt from the live-domain
  `BelongsToProductDomain` guard; staging observer suppression is scoped and
  embedding jobs drain after publish.
- `SourceIdentityRegistry` preserves stable source-to-domain identity mappings.
- Reset Evidence, baseline invariants, search projection state, and embedding
  state are part of the publish/verify contract rather than optional telemetry.

## 5. Delivery Phases

### 5.1. Phase 0 — Baseline and governance

Inventory all previous decisions and execution tickets; establish the new map
as the canonical index; reconcile stale dossier statuses, ADR statuses, and
closed-without-evidence work. Completed issues remain historical prerequisites
and are not duplicated.

### 5.2. Phase 1 — Runtime and schema safety

Restore the local database where necessary, centralize the portfolio-view DDL,
recreate it after every publish, verify search-projection objects survive the
swap, and add regression tests that exercise imports rather than only fresh
migrations.

### 5.3. Phase 2 — Import and reset correctness

Implement migration-built staging, staging model boundaries, observer
suppression, source-to-UUID mapping, self-referential and circular FK handling,
per-product mappers, baseline invariants, Reset Evidence, post-publish
embedding drain, and recovery semantics for Chinook, Northwind, and Pagila.

### 5.4. Phase 3 — Product panels and Admin workflows

Complete Northwind resources, verify all product panel/resource boundaries, and
accept the Admin import/stats workflow against real imports, active reset runs,
view recreation, status polling, cache invalidation, and authorization.

### 5.5. Phase 4 — Search and derived read models

Complete trigger schema isolation and field mapping, verify lexical and vector
projection state after CRUD/import/reset/recovery, drain embeddings, and run the
Golden Search Corpus across product and federated routes.

### 5.6. Phase 5 — Quality, CI, and documentation

Retain completed quality tooling and Pest CI work, but align CI, composer
scripts, ADRs, CONTEXT.md, dossier stages, and operator commands with the
actual PostgreSQL/import architecture and the new lifecycle gates.

### 5.7. Phase 6 — Release acceptance and operations

Collect Baseline Evidence Fixtures, Reset Isolation Proof, Authorization
Acceptance Matrix evidence, Golden Search Corpus evidence, Herd verification,
Linux CI verification, recovery evidence, and final dossier sign-off.

### 5.8. Phase 7 — Closure and stabilization

The closure frontier is tracked by 20 direct GitHub child issues and Beads
children `samples-20260717-7rg.1`–`.20` under the open map #85.

- **Task 12 / #101 / .13 — Teams and Settings:** tracker-closed and locally
  verified. The SFCs were present; failures came from an ignored stale route
  cache generated with the non-testing APP_KEY. After `php artisan route:clear`,
  the focused suite passed 59/59.
- **Task 13 / #102 / .14 — Admin ProductCardActions:** tracker-closed and
  locally verified for the same stale Livewire route cache. The focused suite
  passed 13/13; no widget limitation or production widget change was needed.
- **Task 14 / #103 / .15 — Real source imports:** implementation is present and
  local operator verification passed. Production importers now create isolated
  source schemas, load complete PostgreSQL dumps atomically, build migration-
  backed staging schemas, invoke product mappers, publish atomically, recreate
  the portfolio view, and run invariants/embedding drain. Fresh local runs
  produced 4,652 Chinook, 1,107 Northwind, and 2,534 Pagila search
  projections, with Northwind source-to-target parity across all eleven
  application tables. Full acceptance evidence is recorded against committed
  SHAs below.
- **Task 15 / #104 / .16 — Linux CI:** workflow remediation is implemented and
  validated in PR #126 on committed SHA
  `0f3def7c0a5eb9c276bbc325fe64cf2c89b4a51f`; Tests, TIA, Mutation, CodeQL,
  Semgrep, and PHPStan-related checks are green. The follow-up PR remains
  open for normal review/merge; the evidence gate itself is current.
- **Task 17 / #106 / .18 — PHPStan quality gate:** **tracker-closed and locally
  verified**. Code-level typing fixes and stale baseline cleanup reduced the
  direct PHPStan command to zero errors; full Pest, Pint, Mago, and Architecture
  gates also pass. Linux CI acceptance remains owned by #104.
- **Task 18 / #108 / .19 — Coverage gate:** **tracker-closed and verified**.
  Meaningful source-schema, dump-reader, mapper, import-orchestration, command,
  model, provider, and UI branch coverage now satisfies the configured 100%
  line/type gates locally and in PRs #107 and #126 Linux CI.
- **Task 16 / #105 / .17 — Documentation alignment:** this spec, the delivery
  plan, Wayfinder #85, the implementation-readiness dossier, applicable
  ADR/operator references, and tracker evidence are aligned to the current
  implementation and CI states.

The CI verification task depends on the implementation, real-data evidence,
and documentation-alignment tasks. The epic remains open as execution history
under the living GitHub map; the current implementation and acceptance
evidence is complete, while future follow-up remains eligible to be added
without closing #85.

## 6. Completion Semantics

The roadmap uses four distinct states:

- **Tracker-closed:** the issue was closed in GitHub/Beads.
- **Implemented:** the intended code or document exists in the repository.
- **Verified:** the focused automated and operator checks passed against the
  relevant lifecycle path.
- **Accepted:** the phase's dossier evidence and two-environment gate passed.

No closed historical issue is treated as accepted solely because its issue is
closed. This distinction is mandatory for the portfolio-view failure and is a
project-wide acceptance rule.

## 7. Tracker Model

Maintain the existing GitHub issue labelled `wayfinder:map` titled
**Wayfinder — Samples Application Delivery Roadmap** as the living map. Its
body contains the destination, scope, evidence-backed completed-task index,
current follow-up work, and out-of-scope boundaries. The map remains open so
new evidence can be appended without replacing its history.

Existing issues remain the identity of completed work. The current map has 20
direct execution children, with no duplicate tasks created during
reconciliation. New child issues are created only for remaining deliverables
that are not already represented by a specific issue. Each new issue contains
one question/deliverable, exact acceptance evidence, and dependency
references. Beads mirrors execution tasks; GitHub remains the canonical map
and decision history.

## 8. Acceptance Contract

Every implementation task in the plan must name:

- exact files and interfaces;
- a focused failing or red-capable Pest test before the fix;
- the command that proves the fix;
- Pint/static-analysis requirements for PHP changes;
- operator verification or recovery commands where database state is involved;
- the dossier stage and ADR references it satisfies.

The final roadmap cannot be marked complete until the import path has been
executed with real or reviewed fixtures and the portfolio view, search
projections, embeddings, reset state, and Admin UI are verified after the
schema swap.

## 9. References

- Wayfinder Map #1 — Samples Implementation
- Wayfinder Map #15 — Samples Implementation
- Wayfinder Map #49 — Gap Remediation
- Wayfinder Map #64 — Admin UI Product Import & Stats Refresh Buttons
- Wayfinder Map #73 — Pest 5 Comprehensive Adoption
- ADR 100308 — Shadow-Schema Import Pipeline
- ADR 100314 — Product Reset Semantics
- ADR 100334 — Acceptance & Operational Gates
- ADR 100337 — Single Postgres Test Suite
- `docs/superpowers/plans/2026-08-05-import-pipeline-completion.md`
- `docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`
- `docs/superpowers/specs/2026-08-05-import-deviation-analysis.md`
- `docs/15-delivery/1515-implementation-readiness-dossier/`
- GitHub issues [#101](https://github.com/s-a-c/samples-20260717/issues/101)–[#104](https://github.com/s-a-c/samples-20260717/issues/104) — closure blockers
