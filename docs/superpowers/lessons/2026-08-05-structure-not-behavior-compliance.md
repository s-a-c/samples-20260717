---
title: "Structure-vs-Behavior Compliance: How Three Audits Marked a Destructive Importer ✅"
description: "> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15) · A review of the import-cascade-fix-and-transform plan against map #15's decision history, tracing how two latent failures — a cross-schema CASCADE and an unwired transform layer — survived three compliance reports marked ✅."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: research
tags: [spec, lessons, wayfinder, "15", compliance, import, cascade]
created: 2026-08-05
updated: 2026-08-17
---

# Structure-vs-Behavior Compliance: How Three Audits Marked a Destructive Importer ✅

> **Map:** [Wayfinder — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15)

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Context](#1-context)
- [2. Gap 1 — The CASCADE destroys the portfolio view](#2-gap-1--the-cascade-destroys-the-portfolio-view)
    - [2.1. The decision](#21-the-decision)
    - [2.2. Why it was reasonable then](#22-why-it-was-reasonable-then)
    - [2.3. What changed underneath the decision](#23-what-changed-underneath-the-decision)
    - [2.4. Why nobody caught it](#24-why-nobody-caught-it)
    - [2.5. How the plan resolves it](#25-how-the-plan-resolves-it)
- [3. Gap 2 — The transform layer was decided but never wired in](#3-gap-2--the-transform-layer-was-decided-but-never-wired-in)
    - [3.1. The decisions](#31-the-decisions)
    - [3.2. What shipped instead](#32-what-shipped-instead)
    - [3.3. Why the audits missed it](#33-why-the-audits-missed-it)
    - [3.4. An internal contradiction the plan resolves](#34-an-internal-contradiction-the-plan-resolves)
- [4. Coverage matrix — what the plan resolves, and what it doesn't](#4-coverage-matrix--what-the-plan-resolves-and-what-it-doesnt)
- [5. The deeper failure — auditing structure instead of behavior](#5-the-deeper-failure--auditing-structure-instead-of-behavior)
- [6. Two decision deviations the plan makes without recording](#6-two-decision-deviations-the-plan-makes-without-recording)
- [7. Artifacts](#7-artifacts)
- [8. References](#8-references)

</details>

---

## 1. Context

This document is the **review output** for the implementation plan
[`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`](../plans/2026-08-05-import-cascade-fix-and-transform.md),
checked against [Wayfinder Map #15](https://github.com/s-a-c/samples-20260717/issues/15)
and its decision history.

Map #15 ("Wayfinder — Samples Implementation") is **CLOSED** — all 27 child
decision tickets resolved, the architecture fully charted across eight
Acceptance Stages. Three compliance audits (2026-07-25 → 2026-07-28 →
2026-07-29) walked it to "11/12 clusters fully compliant, 0 missing."

So the plan isn't filling **charting** gaps — map #15 decided everything.
It's fixing **decision-implementation drift**: places where the map decided
one thing, code shipped something else, and the audits marked it ✅ anyway.

The plan resolves **two** latent failures. Both arose after the relevant
decisions closed. Both survived three compliance reports. Both stem from one
deeper pattern, examined in §5.

---

## 2. Gap 1 — The CASCADE destroys the portfolio view

A `DROP SCHEMA … CASCADE` pattern, deliberately chosen as a clean atomic
reset, silently destroys a cross-schema view added two days later.

### 2.1. The decision

Map #15 made the `DROP SCHEMA … CASCADE` pattern a **deliberate, two-part
decision**:

- **Decision #14 ([#28](https://github.com/s-a-c/samples-20260717/issues/28),
  Product Import pipeline):** _"publish = atomic `DROP SCHEMA CASCADE` +
  `ALTER SCHEMA RENAME`"_ — the shadow-schema-swap staging.
- **Decision #3 ([#41](https://github.com/s-a-c/samples-20260717/issues/41),
  Postgres schema design):** _"Search Projections per-product; drops
  atomically with `DROP SCHEMA … CASCADE` during Product Reset."_ The
  per-product schemas can be nuked and rebuilt wholesale.
  [`source_identities`](../../../database/migrations/0001_01_01_000001_create_source_identities_table.php)
  was placed in `public` _"so it survives per-product reset"_ (Decision #10,
  [#25](https://github.com/s-a-c/samples-20260717/issues/25)).

### 2.2. Why it was reasonable then

At decision time (2026-07-20 → 25), the only things depending on the product
schemas lived **inside** them. CASCADE was genuinely safe: it dropped
`chinook.*` and the schema migration replayed to rebuild it. The registry in
`public` survived "by location." The reasoning was sound **given the
dependency graph as it then stood**.

### 2.3. What changed underneath the decision

Two days later (2026-07-27, remediation **T9**, commit `a2f2818`), the
[`product_portfolio_snapshots`](../../../database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php)
**VIEW** was added — in `public`, but `UNION ALL`-ing over `chinook.artists`,
`chinook.tracks`, `northwind.products`, `northwind.orders`, `pagila.films`,
`pagila.actors`. Postgres records the view's dependency on those objects.

So when the importer runs `DROP SCHEMA chinook CASCADE`, the cascade now
traverses the dependency edge **back into `public`** and silently drops the
view — and each product's `*.search_projections` objects (added `e8127c9`,
living **inside** the product schemas: the tables, the GIN/HNSW indexes, the
PL/pgSQL trigger functions, the triggers).

### 2.4. Why nobody caught it

The T9 remediation created the view migration but **never re-examined the
cascade decision** (#28/#41) to account for the new cross-schema dependency.
A view in `public` that depends on per-product schemas is a new edge in the
dependency graph that the original decision's safety premise never considered.

The three compliance reports marked **Portfolio Card ✅** (view exists) and
**Import Pipeline ✅** (machinery present) in the _same audit_ — the two green
checks contradicted each other, but each audit checked **structure** (does the
file/class exist), not **behavior** (does an import leave the view intact).

The 2026-07-31 admin-import-buttons work (separate
[map #64](https://github.com/s-a-c/samples-20260717/issues/64)) then treated
the pipeline as a "pre-existing, working black box," mocked
`ProductImportPipeline` in every test, and built a UI on top of an importer
that destroyed the very view its own "auto-refresh stats on import complete"
polling badge tried to read.

### 2.5. How the plan resolves it

[Phase A](../plans/2026-08-05-import-cascade-fix-and-transform.md#phase-a--defang-the-cascade)
of the plan removes the CASCADE from both the schema migrations and the
importers — defanging the decision's blast radius. This is a **deviation from
Decision #28/#41**: it abandons the shadow-schema-swap in favor of in-place
truncate-and-reload. It is justified because the original premise — _"only
per-product objects are dropped"_ — was invalidated by the cross-schema view.
**Flag for the record:** this deviation must itself be captured as a decision
(an ADR superseding the swap mechanism, or a re-opening ticket), not left as a
silent code change. See §6.

---

## 3. Gap 2 — The transform layer was decided but never wired in

The upstream→UUID transform was designed in full detail across three tickets
— and then never connected. A built-but-unwired `SourceIdentityRegistry` sits
in the codebase, unit-tested and marked ✅, while zero rows are ever
transformed.

### 3.1. The decisions

Map #15 decided the upstream→UUID transform in detail:

- **Decision #9 ([#24](https://github.com/s-a-c/samples-20260717/issues/24),
  UUIDv7):** _"import hook = explicit-id-on-reuse — importer sets
  `$model->id` from the registry on a hit, leaves it unset on a miss."_
- **Decision #10 ([#25](https://github.com/s-a-c/samples-20260717/issues/25),
  Source Identity Registry):** built
  [`public.source_identities`](../../../app/Services/ProductImport/SourceIdentityRegistry.php),
  `getOrMint()`, JSONB keys, the `entity` CHECK constraint — the complete
  FK-translation layer.
- **Decision #14 ([#28](https://github.com/s-a-c/samples-20260717/issues/28)):**
  _"Eloquent model-per-row processing (triggers fire naturally per #32)."_

### 3.2. What shipped instead

The importers (commits `0a717a4` 2026-07-25, then `ac30db2` for the
Sakila→Pagila pivot) do the schema swap and **never call the registry once**.
Zero rows are ever transformed. The `SourceIdentityRegistry` exists, is
unit-tested, and is marked ✅ compliant — but it is a built-but-unwired organ.
The importer "succeeds" today only because no source dump is cached
(`storage/app/private/sources/*` is empty), so it swaps in an empty staging
schema and nothing fails.

### 3.3. Why the audits missed it

All three compliance reports marked **Import Pipeline ✅** and **Source
Identity Registry ✅** throughout — they checked _presence_ (class exists,
method exists, migration present), not _behavior_ (does an import populate
domain tables with correct UUIDs). And every importer test either mocks the
pipeline or runs with no source file, so the missing transform never surfaced
as a test failure.

### 3.4. An internal contradiction the plan resolves

Decision #28 said _"Eloquent model-per-row processing,"_ but Decision #29
([#29](https://github.com/s-a-c/samples-20260717/issues/29), Reset semantics)
added the
[`BelongsToProductDomain`](../../../app/Traits/BelongsToProductDomain.php) trait
— a trait that **blocks all Eloquent writes during a running `ResetRun`**.
Since
[`ProductImportPipeline::run()`](../../../app/Services/ProductImport/ProductImportPipeline.php)
marks the run `running` _before_ calling the importer, Eloquent inserts would
throw `ProductResetWindowOpen`. The two decisions conflict.

The plan resolves it in favor of #29 (the safety-critical one): **raw
`DB::table()->insert()`**, which sidesteps the Eloquent hook entirely. This is
correct, but again a **deviation from the recorded #28 design** that deserves
to be captured (see §6).

---

## 4. Coverage matrix — what the plan resolves, and what it doesn't

| Gap                                               | Origin                                                               | Plan coverage                                                                  |
| ------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| CASCADE destroys the view + search projections    | Decision #28/#41 + T9 added a cross-schema dep without re-checking   | Phase A ✅                                                                     |
| Importer never transforms rows (registry unwired) | Decisions #9/#10/#14 decided but `0a717a4` implemented only the swap | Phases B–E ✅                                                                  |
| Eloquent-blocked-during-run contradiction         | #28 ("Eloquent per-row") vs #29 (`BelongsToProductDomain`)           | Plan uses raw inserts ✅ — but the decision conflict isn't recorded            |
| Audit checked structure not behavior              | All 3 reports marked machinery ✅ without an end-to-end load test    | Plan adds `Transform*Test.php` (real rows → real UUIDs → real FK integrity) ✅ |
| Mock-based tests hid the behavior                 | 2026-07-31 plan mocked the pipeline everywhere                       | Plan adds fixture-driven tests ✅                                              |
| Decision deviations not captured as ADRs          | Plan changes recorded #28/#41 decisions in code only                 | ❌ Plan has no ADR-recording step                                              |
| No standing behavioral-compliance rule            | Audits can repeat the structure-not-behavior mistake                 | ❌ Plan has no standing rule                                                   |

---

## 5. The deeper failure — auditing structure instead of behavior

Both gaps survived three compliance audits for the same reason: **the audits
verified that the machinery existed, not that the machinery worked.** A class
file present, a migration file present, a method signature correct — each
ticked ✅. None asked: _"if I run a real import, do the domain tables end up
populated with correct UUIDs and intact FK chains, and is the portfolio view
still queryable afterward?"_

A single end-to-end behavioral test — load real fixture data through the
importer and assert the post-conditions on the live database — would have
caught **both** gaps at the first audit:

- The view would have been missing after the import (Gap 1).
- The domain tables would have been empty or held incompatible integer-PK
  upstream rows (Gap 2).

This is the lesson that generalizes beyond the import pipeline. **Compliance
that checks structure without exercising behavior can mark a broken system
✅ indefinitely**, because each artifact can be correct in isolation while the
integration between them is broken. The mock-based tests compounded this by
replacing the pipeline with a stub at every call site, so even the
admin-import-buttons feature — which _depends_ on imports working — never ran
a real import.

**Remediation:** a standing behavioral-compliance check is required as a
prerequisite to the transform plan — a test that loads a real fixture through
each importer and asserts the end state. This is tracked as a blocking
prerequisite issue (see §7).

---

## 6. Two decision deviations the plan makes without recording

The plan, as written, deviates from two recorded map-#15 decisions. Both are
defensible, but neither is captured as a superseding decision:

1. **Abandoning the shadow-schema-swap** (Decision #28 / #41). The plan
   replaces `DROP SCHEMA … CASCADE` + `ALTER SCHEMA … RENAME` with in-place
   truncate-and-reload. Justification: the original premise ("only
   per-product objects are dropped") was invalidated by the cross-schema
   `product_portfolio_snapshots` view and the in-schema search-projection
   objects. Truncate-and-reload preserves dependent objects.

2. **Switching from Eloquent-per-row to raw `DB::table()->insert()`**
   (Decision #14 / #28 vs Decision #29). The plan uses raw inserts because
   `BelongsToProductDomain` (from #29) blocks Eloquent writes during a running
   `ResetRun`, and the pipeline marks the run `running` before calling the
   importer. Justification: #29 is the safety-critical decision and must win;
   raw inserts satisfy it while still populating domain tables.

Per the project's ADR mandate (AGENTS.md §5), these deviations should be
captured as ADRs that supersede the relevant parts of #28/#41 — not left as
silent code changes. A handoff prompt has been written to analyze the
consequences of these deviations before they are locked in. See §7.

---

## 7. Artifacts

This lesson produced three follow-up artifacts:

| Artifact                  | Location                                                                                                                               | Purpose                                                                                                                                                             |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| The plan                  | [`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`](../plans/2026-08-05-import-cascade-fix-and-transform.md)     | Fixes both gaps (Phase A defangs CASCADE; Phases B–E build the transform)                                                                                           |
| Prerequisite issue + bead | GitHub issue (search `behavioral-compliance-check`); bd bead synced                                                                    | A standing behavioral-compliance test that loads a real fixture through each importer and asserts the post-state. **Must land before the plan's transform phases.** |
| Handoff prompt            | [`docs/superpowers/handoffs/2026-08-05-import-decision-deviations.md`](../../agents/handoffs/2026-08-05-import-decision-deviations.md) | Analyzes the two decision deviations (swap abandonment, Eloquent→raw inserts) before they are locked in as superseding ADRs                                         |

---

## 8. References

- [Wayfinder Map #15 — Samples Implementation](https://github.com/s-a-c/samples-20260717/issues/15) (CLOSED, 27/27 child tickets resolved)
- [#28 — Decide the Product Import pipeline shape](https://github.com/s-a-c/samples-20260717/issues/28) (Decision #14: shadow-schema-swap, Eloquent-per-row)
- [#29 — Decide the Product Reset semantics](https://github.com/s-a-c/samples-20260717/issues/29) (Decision #15: `BelongsToProductDomain` write-block)
- [#41 — Postgres schema design](https://github.com/s-a-c/samples-20260717/issues/41) (Decision #3: per-product `DROP SCHEMA CASCADE`)
- [#24 — UUIDv7 strategy](https://github.com/s-a-c/samples-20260717/issues/24) (Decision #9: explicit-id-on-reuse)
- [#25 — Source Identity Registry](https://github.com/s-a-c/samples-20260717/issues/25) (Decision #10: built-but-unwired FK-translation layer)
- [#35 — Portfolio Card architecture](https://github.com/s-a-c/samples-20260717/issues/35) (Decision #22: the `product_portfolio_snapshots` view)
- [2026-07-25 compliance report](../specs/2026-07-25-wayfinder-15-compliance-report.md) (10 gaps)
- [2026-07-28 compliance report](../specs/2026-07-28-wayfinder-15-compliance-report.md) (7 G-gaps)
- [2026-07-29 compliance report](../specs/2026-07-29-wayfinder-15-compliance-report.md) (4 R-gaps)
- [Import cascade fix & transform plan](../plans/2026-08-05-import-cascade-fix-and-transform.md)
