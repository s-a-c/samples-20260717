---
title: "Import Decision Deviations — Consequence Analysis Handoff"
description: "> **Handoff for:** a follow-up agent session to analyze the consequences of two decision deviations in the import-cascade-fix-and-transform plan before they are locked in as superseding ADRs. Further analysis is required."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: handoff
tags: [handoff, wayfinder, "15", import, adr, decisions]
created: 2026-08-05
updated: 2026-08-17
---

# Import Decision Deviations — Consequence Analysis Handoff

> **Handoff for:** a follow-up agent session.
> **Origin:** [`docs/superpowers/lessons/2026-08-05-structure-not-behavior-compliance.md`](../../superpowers/lessons/2026-08-05-structure-not-behavior-compliance.md) §6.
> **Related plan:** [`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`](../../superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md).
> **Related issue:** [#81 — behavioral-compliance check](https://github.com/s-a-c/samples-20260717/issues/81).

---

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Your task](#1-your-task)
- [2. Skills to invoke before starting](#2-skills-to-invoke-before-starting)
- [3. The two deviations](#3-the-two-deviations)
    - [3.1. Deviation 1 — Abandoning the shadow-schema-swap](#31-deviation-1--abandoning-the-shadow-schema-swap)
    - [3.2. Deviation 2 — Raw `DB::table()->insert()` instead of Eloquent-per-row](#32-deviation-2--raw-dbtable-insert-instead-of-eloquent-per-row)
- [4. What to analyze](#4-what-to-analyze)
    - [4.1. A. What breaks if we ratify the deviation](#41-a-what-breaks-if-we-ratify-the-deviation)
    - [4.2. B. What breaks if we revert to the recorded decision](#42-b-what-breaks-if-we-revert-to-the-recorded-decision)
    - [4.3. C. Third options not yet considered](#43-c-third-options-not-yet-considered)
    - [4.4. D. Recommendation](#44-d-recommendation)
- [5. Constraints](#5-constraints)
- [6. Deliverables](#6-deliverables)
- [7. Out of scope](#7-out-of-scope)

</details>

---

## 1. Your task

The import-cascade-fix-and-transform plan, as written, **deviates from two
recorded Wayfinder Map #15 decisions**. Both deviations are defensible, but
neither has been analyzed for downstream consequences, and neither is captured
as a superseding decision (ADR). **Your job is to analyze the consequences of
each deviation and produce the superseding ADR(s) — or recommend reverting the
plan to match the recorded decisions.**

This is **analysis-first**, not implementation. Do not start coding the plan.
Produce a written consequence analysis for each deviation, then decide (with
the operator) whether to ratify each as a superseding ADR or to revise the
plan.

## 2. Skills to invoke before starting

Per `using-superpowers`: invoke `superpowers:brainstorming` first (this is a
design/decision task, not a bugfix), then `wayfinder` (you are working a
decision that re-opens recorded map-#15 resolutions), and `domain-modeling`
for the FK/write-semantics reasoning. Announce each as you invoke it.

## 3. The two deviations

### 3.1. Deviation 1 — Abandoning the shadow-schema-swap

**Recorded decision (#28 / #41):** Product Import publish = atomic
`DROP SCHEMA <product> CASCADE` + `ALTER SCHEMA <product>_staging RENAME TO
<product>`. Rationale: atomic per-product reset, clean rebuild via migration
replay, `source_identities` survives in `public` "by location."

**Plan deviation (Phase A):** remove the CASCADE entirely from both the
schema migrations' `up()` and the importers. Replace with in-place
truncate-and-reload of the domain tables (the transform layer in Phases B–E
does `DB::table()->truncate()` + `DB::table()->insert()`).

**Why the plan deviates:** the original premise — "only per-product objects
are dropped" — was invalidated when T9 added the `product_portfolio_snapshots`
view (a `public` view depending on per-product tables) and the search-projection
objects (living _inside_ the product schemas). CASCADE now destroys both.

### 3.2. Deviation 2 — Raw `DB::table()->insert()` instead of Eloquent-per-row

**Recorded decision (#28):** _"Eloquent model-per-row processing (triggers
fire naturally per #32)."_

**Plan deviation (Phase B, Task B2):** the transform layer uses raw
`DB::table()->insert()`, not Eloquent models.

**Why the plan deviates:** Decision #29 added the `BelongsToProductDomain`
trait, which boots `creating`/`updating`/`deleting` hooks that call
`ResetWindow::assertWritable()` — throwing `ProductResetWindowOpen` while a
`ResetRun` is `running`. Since `ProductImportPipeline::run()` marks the run
`running` _before_ calling the importer, Eloquent writes are blocked for the
entire import. The two decisions (#28 "Eloquent per-row" vs #29 "block writes
during run") are in direct conflict.

---

## 4. What to analyze

For **each** deviation, produce a written analysis covering:

### 4.1. A. What breaks if we ratify the deviation

- Which other recorded decisions, ADRs, code, or tests assume the _original_
  behavior? Trace the dependencies. (e.g. does any arch rule, any observer,
  any Reset Recovery path assume the swap happened?)
- Does the deviation affect the **8 Baseline Invariants** that Decision #28
  said gate the publish step? List each invariant and whether it still holds
  under truncate-and-reload.
- For Deviation 2: does bypassing Eloquent also bypass the
  `Tier1SourceObserver` (the observer that queues `EmbeddingJob`s on
  `saved`)? If so, how do embeddings get queued during an import? Decision
  #28 explicitly said "observer's queue path suppressed for staging bulk-load"
  — reconcile this with #32's "Eloquent observer queues embedding job
  post-commit." Which path actually fires under raw inserts? **This is the
  single most important question to resolve** — it determines whether search
  works after an import.

### 4.2. B. What breaks if we revert to the recorded decision

- If we instead _keep_ the CASCADE swap, how do we protect the
  `product_portfolio_snapshots` view and the search-projection objects?
  Options to weigh: (i) recreate them after every swap; (ii) move the view
  into a schema that survives; (iii) make the view resilient to its
  dependencies being dropped. What are the trade-offs?
- If we instead use Eloquent-per-row, how do we reconcile with #29's
  write-block? Options: (i) suspend the trait during import; (ii) move the
  run-status transition to _after_ the import; (iii) use a separate
  non-Eloquent write path. What does each cost in safety?

### 4.3. C. Third options not yet considered

- Is there a hybrid? (e.g. keep the swap for full Product Reset, but use
  truncate-and-reload for Import only — since Import and Reset are
  conceptually different operations that #28 unified as "first-import =
  reset").
- Is `DISABLE TRIGGER` / session_replication_role a better lever for
  Deviation 2 than raw inserts? (It would let Eloquent write while suspending
  both the domain gate _and_ the FK checks — but it has security implications
  and the project arch rules may forbid it.)
- Does the search-projection trigger still fire on raw `DB::table()->insert()`?
  (Postgres triggers fire on INSERT regardless of which client issued it —
  Eloquent or raw — so the _trigger_ should fire; it's the Eloquent
  _observer_ (PHP-side, queues the embedding job) that won't. Confirm this
  distinction and its consequences.)

### 4.4. D. Recommendation

For each deviation, recommend one of:

- **Ratify** as a superseding ADR (draft the ADR content).
- **Revert** the plan to match the recorded decision (identify the plan
  changes to undo).
- **Hybrid** (specify the hybrid and its boundary).

## 5. Constraints

- Read the actual decision text before analyzing:
  `gh issue view 28` and `gh issue view 29` and `gh issue view 41` (the
  recorded resolutions, not summaries).
- Read the conflicting code: `app/Traits/BelongsToProductDomain.php`,
  `app/Observers/Tier1SourceObserver.php`, `app/Jobs/EmbeddingJob.php`,
  `app/Services/ProductReset/ResetWindow.php`,
  `app/Services/ProductImport/ProductImportPipeline.php`.
- Cite decision numbers (#28, #29, #41, #32) and ADR numbers precisely.
- Per AGENTS.md §5, any ratified deviation becomes a superseding ADR under
  `docs/10-architecture/1003-adr/`.

## 6. Deliverables

1. A consequence-analysis document per deviation (A–D above), saved to
   `docs/superpowers/specs/2026-08-05-import-deviation-analysis.md`.
2. For each ratified deviation, a drafted superseding ADR.
3. Any required updates to the plan
   (`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`)
   if the analysis changes the approach.
4. A bd bead / GitHub issue for the ADR-writing work, if it isn't done in
   this session.

## 7. Out of scope

- Implementing the import-cascade-fix-and-transform plan itself. That waits
  until (a) this analysis lands and (b) issue #81 (behavioral-compliance
  check) exists.
