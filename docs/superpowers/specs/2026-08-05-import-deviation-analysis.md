---
title: "Import Decision Deviations — Consequence Analysis"
description: >-
    Analysis of two deviations in the import-cascade-fix-and-transform plan from
    Wayfinder Map #15 decisions (#28, #29, #41, #32). Recommendation: revert both.
    Not an implementation; a decision input.
type: spec
tags: [spec, wayfinder, "15", import, adr, decisions, analysis]
updated: 2026-08-05
---

# Import Decision Deviations — Consequence Analysis

> **Origin:** [`docs/superpowers/handoffs/2026-08-05-import-decision-deviations.md`](../handoffs/2026-08-05-import-decision-deviations.md).
> **Plan under analysis:** [`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`](../plans/2026-08-05-import-cascade-fix-and-transform.md).
> **Decisions of record:** Map #15 tickets [#28](https://github.com/s-a-c/samples-20260717/issues/28), [#29](https://github.com/s-a-c/samples-20260717/issues/29), [#41](https://github.com/s-a-c/samples-20260717/issues/41), [#32](https://github.com/s-a-c/samples-20260717/issues/32).
> **Status:** Analysis only. Does not implement the plan. ADR drafting is filed separately.

---

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Reader's guide](#1-readers-guide)
    - [1.1. Recommendations at a glance](#11-recommendations-at-a-glance)
- [2. §0. The shared root cause — #29's stated exemption was never built](#2-0-the-shared-root-cause--29s-stated-exemption-was-never-built)
- [3. §1. Deviation 2 — Raw `DB::table()->insert()` instead of Eloquent-per-row](#3-1-deviation-2--raw-dbtable-insert-instead-of-eloquent-per-row)
    - [3.1. The recorded decision](#31-the-recorded-decision)
    - [3.2. The plan deviation](#32-the-plan-deviation)
    - [3.3. Why the plan deviates](#33-why-the-plan-deviates)
    - [3.4. A. What breaks if we ratify the deviation](#34-a-what-breaks-if-we-ratify-the-deviation)
    - [3.5. B. What breaks if we revert to Eloquent-per-row](#35-b-what-breaks-if-we-revert-to-eloquent-per-row)
    - [3.6. C. Third options considered](#36-c-third-options-considered)
    - [3.7. D. Recommendation: Revert](#37-d-recommendation-revert)
- [4. §2. Deviation 1 — Abandoning the shadow-schema-swap](#4-2-deviation-1--abandoning-the-shadow-schema-swap)
    - [4.1. The recorded decision](#41-the-recorded-decision)
    - [4.2. The plan deviation](#42-the-plan-deviation)
    - [4.3. Why the plan deviates](#43-why-the-plan-deviates)
    - [4.4. A. What breaks if we ratify the deviation](#44-a-what-breaks-if-we-ratify-the-deviation)
    - [4.5. B. What breaks if we revert (keep the swap)](#45-b-what-breaks-if-we-revert-keep-the-swap)
    - [4.6. C. Third options considered](#46-c-third-options-considered)
    - [4.7. D. Recommendation: Revert](#47-d-recommendation-revert)
- [5. §3. Required revisions to the plan](#5-3-required-revisions-to-the-plan)
    - [5.1. Phase A — restructured](#51-phase-a--restructured)
    - [5.2. Phase B — transform writes through Eloquent, to a migration-built staging](#52-phase-b--transform-writes-through-eloquent-to-a-migration-built-staging)
    - [5.3. Phases C–E — mapper families](#53-phases-ce--mapper-families)
    - [5.4. Phase F — wiring](#54-phase-f--wiring)
    - [5.5. Open questions for the revision](#55-open-questions-for-the-revision)
- [6. §4. ADR and follow-up work (filed, not executed here)](#6-4-adr-and-follow-up-work-filed-not-executed-here)
- [7. §5. The eight Baseline Invariants under each path](#7-5-the-eight-baseline-invariants-under-each-path)
- [8. References](#8-references)

</details>

---

## 1. Reader's guide

The plan deviates from two recorded Map #15 decisions. The handoff framed these
as two independent deviations to analyze separately. After reading the decision
text and the conflicting code, this analysis concludes they are **not
independent** — both are symptoms of a single unrealized design intent. Section
§0 names the shared root cause; §1 and §2 then analyze each deviation against
that frame.

Per the operator's steer, the analysis prefers **retaining Eloquent as the sole
write path** where it is possible. It is not only possible — it is the _only_
path that does not silently break semantic search. Both deviations should be
reverted.

### 1.1. Recommendations at a glance

| #   | Deviation                                      | Recommendation             | One-line reason                                                                                                                                            |
| --- | ---------------------------------------------- | -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Abandon shadow-swap → truncate-and-reload      | **Revert** (keep the swap) | The one real victim (the `public` view) is trivially recreated post-swap; everything else rebuilds via migration replay — which is the swap's whole point. |
| 2   | Eloquent-per-row → raw `DB::table()->insert()` | **Revert** (keep Eloquent) | Raw inserts silently break semantic search: the Postgres trigger fires but the PHP observer does not, so embeddings are never queued.                      |

---

## 2. §0. The shared root cause — #29's stated exemption was never built

Both deviations dissolve into one fact: **#29 Decision 2 describes a staging
write exemption that does not exist in the code.**

#29 Decision 2, verbatim intent:

> Importer's staging writes are exempt because they target `<product>_staging`
> (different schema, different model-table attribute per #41).

But the trait #29 mandates gates by **product**, not by **schema**. From
`app/Traits/BelongsToProductDomain.php`:

```php
public static function bootBelongsToProductDomain(): void
{
    static::creating(function (HasProductDomain $model) {
        app(ResetWindow::class)->assertWritable($model->getProductDomain());
    });
    // ... updating, deleting identical
}
```

`getProductDomain()` returns a hardcoded `SamplesProduct` enum (`Chinook`,
`Northwind`, `Pagila`). `ResetWindow::assertWritable()` checks whether a `ResetRun`
for that _product_ is active. **There is no schema check anywhere in this
chain.** An Eloquent write to `chinook_staging.artists` through a `Chinook\Artist`
model throws `ProductResetWindowOpen` exactly as readily as a write to
`chinook.artists` — because the model reports `getProductDomain() === Chinook`
either way, and the run is `running`.

This was the trap. `ProductImportPipeline::run()` marks the run `running`
_before_ calling the importer, so under the shadow-swap design (#28 Decision 3),
staging writes through domain models are blocked by a gate that #29 _said_ would
exempt them. The plan's author, hitting this block, chose raw
`DB::table()->insert()` (Deviation 2) as a workaround. Having abandoned Eloquent
for raw inserts, the staging schema offered no further benefit, so the swap
itself was abandoned too (Deviation 1) in favor of truncate-and-reload on the
live schema.

**The fix is not to ratify either deviation. It is to make #29's stated
exemption real.** Once staging writes genuinely do not hit the trait, the
shadow-swap (#28 Decision 3) works as designed, Eloquent-per-row (#28 Decision 7)
works as designed, and neither deviation is needed.

A second, related dead-code finding reinforces this: the observer suppression
guard #28 Decision 15 relies on was also never wired. `Tier1SourceObserver`
reads:

```php
if (app()->bound('is_staging') && app('is_staging') === true) {
    return;
}
```

…but `is_staging` is bound **nowhere in `app/`** (confirmed by grep). #28
Decision 15 specified "during initial bulk-load staging, the observer's queue
path is suppressed." The guard implementing that intent is present but inert. So
even if staging writes went through Eloquent today, the observer would dispatch
an `EmbeddingJob` per row during staging — exactly what #28 Decision 15 said
should not happen.

These two unrealized intents — the staging exemption (#29) and the observer
suppression (#28 Decision 15) — together explain why the plan deviated. They are
also both narrow, fixable gaps, not structural flaws.

---

## 3. §1. Deviation 2 — Raw `DB::table()->insert()` instead of Eloquent-per-row

> Analyzed first because it is load-bearing: the swap abandonment (§2) was a
> downstream consequence of this choice, not an independent decision.

### 3.1. The recorded decision

**#28 Decision 7** — _"Row processing model: Eloquent model per row. For each
source row: construct the target model, set attributes from source + registry
lookup (#25), `$model->save()`. Sample-dataset scale (~50K rows total) makes
Eloquent-save latency invisible; bulk-insert alternatives rejected for breaking
#32's observer contract and re-implementing #6's type-coercion rules."_

**#28 Decision 15** refined the observer interaction: during bulk-load staging,
the observer's queue path is suppressed (trigger still writes the projection with
`embedding_state='pending'`); after publish, the rebuild phase queues embedding
jobs and waits for drain.

### 3.2. The plan deviation

**Phase B, Task B2** — the transform layer uses `DB::table()->insert()`:

```php
// app/Services/ProductImport/Mapping/TableMapper.php (plan, line ~643)
DB::table($this->domainTable())->insert($domain);
```

### 3.3. Why the plan deviates

**#29 Decision 2** added `BelongsToProductDomain`, which throws
`ProductResetWindowOpen` during a running `ResetRun`. The plan correctly observed
that `ProductImportPipeline::run()` marks the run `running` before calling the
importer, so Eloquent writes are blocked. Raw inserts dodge the trait entirely.

### 3.4. A. What breaks if we ratify the deviation

**A.1 — Embeddings are silently never queued (the handoff's central question,
confirmed in code).**

There are two independent write paths in this system, and they trigger different
machinery:

| Write path                  | Postgres trigger fires? (→ projection row written) | `Tier1SourceObserver::saved()` fires? (→ `EmbeddingJob` dispatched) |
| --------------------------- | -------------------------------------------------- | ------------------------------------------------------------------- |
| Eloquent `$model->save()`   | ✅ yes                                             | ✅ yes                                                              |
| Raw `DB::table()->insert()` | ✅ yes                                             | ❌ **no**                                                           |

The Postgres trigger (`trg_chinook_artists_search`, defined in the `210001`
migration) is `AFTER INSERT OR UPDATE OR DELETE … FOR EACH ROW` — it fires on
_any_ row write, regardless of client. So raw inserts **do** populate
`chinook.search_projections` with `embedding_state = 'pending'`. The lexical
projection (the `document_tsv` generated column) is also computed correctly.

But `Tier1SourceObserver` is an Eloquent _model event_ listener, registered via
`Artist::observe(Tier1SourceObserver::class)` in `AppServiceProvider`. Raw
`DB::table()->insert()` bypasses Eloquent entirely — no model is instantiated, no
`saved` event fires, no `EmbeddingJob::dispatch()` runs. **Every projection row
written by the transform stays `pending` indefinitely.** Lexical search works;
semantic search returns empty results. The failure is silent: no exception, no
log entry, no metric. An operator would notice only by querying
`embedding_state` counts or discovering semantic search returns nothing.

This is the single most damaging consequence of the deviation, and it is not
flagged anywhere in the plan.

**A.2 — The `is_staging` suppression guard is dead code.**

Independent of the deviation, the guard #28 Decision 15 specified — "observer's
queue path is suppressed during staging" — is present in
`Tier1SourceObserver` but never armed. `is_staging` is bound nowhere in `app/`
(confirmed: `grep -rn "is_staging" app/` returns only the observer's own read).
So even if staging writes went through Eloquent, every row would dispatch an
`EmbeddingJob` during staging — precisely the per-row network call #28 Decision
15 said to suppress. This must be fixed regardless of which path is chosen.

**A.3 — Invariant 8 (embedding lifecycle) can never pass.**

#28 Decision 10, Invariant 8: the verify phase counts
`embedding_state NOT IN ('complete','lexical_only')` and requires zero. Under
raw inserts, every transform-written row sits at `pending` forever — the count
never reaches zero, the verify phase never passes, the run can never reach
`succeeded`. (The plan does not implement Invariant 8 yet, but the deviation
makes it structurally un-passable when it is implemented.)

**A.4 — The #29 arch rule's intent is defeated.**

#29 added the arch rule: _"every concrete model under
`App\Domain\{Chinook,Northwind,Sakila}\Models\` MUST use the
`BelongsToProductDomain` trait."_ Raw inserts don't _violate_ this rule (the
models still carry the trait), but they sidestep it — the trait's guarantee
("every write is gated") no longer holds for import-path writes. A future
maintainer reading the arch rule would reasonably believe all writes are gated;
the raw-insert path quietly breaks that assumption.

**A.5 — #32's integrity-enforcement model is half-applied.**

#32 specified belt-and-suspenders: trigger (DB-enforced content writer) +
observer (app-level embedding reactor). Raw inserts keep the trigger half but
drop the observer half — during import, the embedding reactor simply does not
run. #32's split assumed Eloquent writes; the deviation breaks the assumption.

### 3.5. B. What breaks if we revert to Eloquent-per-row

**B.1 — The #28-vs-#29 conflict is real _as built_, but only against the live
schema.**

As traced in §0, the trait gates by product, so Eloquent writes to a
product-domain model throw during a running `ResetRun`. This is a genuine block —
_if_ writes target the live `<product>` schema.

But under the shadow-swap (#28 Decision 3), staging writes target
`<product>_staging`. #29 Decision 2 explicitly intended staging writes to be
exempt. The block exists only because that exemption was never implemented.
Reverting to Eloquent requires making the exemption real (see §C).

**B.2 — Per-row observer dispatch during staging, if unsuppressed.**

If staging writes go through Eloquent and the `is_staging` guard remains unwired,
every row's `saved` event dispatches an `EmbeddingJob`. At ~50K rows this is
50,000 queued jobs during staging — and #28 Decision 15 explicitly ruled this
out ("decouples staging from external API calls"). So reverting to Eloquent
_requires_ wiring the `is_staging` flag. Fortunately that is a one-line bind
(`app()->instance('is_staging', true)` for the staging-build duration).

**B.3 — Eloquent save latency.**

#28 Decision 7 already weighed and accepted this: ~50K rows total across three
products, Eloquent-save overhead invisible at sample scale. Latency is not a
reason to deviate.

### 3.6. C. Third options considered

**C.1 — `session_replication_role = replica` / `DISABLE TRIGGER`.** This is a
DB-level hammer: it suspends _Postgres_ triggers and FK checks for the session.
It does nothing about the _PHP-side_ `BelongsToProductDomain` trait (which runs
in application code before any SQL is issued), so it does not resolve the
#28-vs-#29 conflict. It would also disable the search-projection trigger —
meaning projection rows would not be written either, breaking lexical search
too. Rejected: solves the wrong layer, breaks more than it fixes.

**C.2 — A scoped `ResetWindow::withoutGuard()` bypass during import.** This would
let Eloquent writes through the trait during a declared scope. It works, but it
pokes a deliberate hole in the write-safety gate that #29 exists to enforce. The
staging-exemption approach (#29's own stated design) achieves the same end
without weakening the gate for non-staging paths. Prefer the narrower exemption.

### 3.7. D. Recommendation: Revert

Keep Eloquent-per-row (#28 Decision 7). Make #29's staging exemption real so
the #28-vs-#29 conflict resolves as #29 always intended. Concretely:

1. **Staging writes resolve through models that do not carry
   `BelongsToProductDomain`.** Two implementation shapes fit #41's
   schema-qualified design:
    - **Staging-resolved model subclasses** — e.g. `Chinook\Staging\Artist`
      extends `Chinook\Artist`, overrides the table to `chinook_staging.artists`,
      and does not use the trait. Clean, explicit, arch-testable.
    - **`setTable`-based routing** — reuse the domain model, call
      `$model->setTable('chinook_staging.artists')` per instance. Lighter, but
      the trait still boots and still gates — so this shape alone does not solve
      the conflict; it needs the subclass shape to actually drop the trait.

    The subclass shape is the one that makes the exemption _real_ (trait absent,
    not merely bypassed). The arch rule from #29 should be refined to scope the
    trait mandate to _live-schema_ models, with staging subclasses explicitly
    carved out.

2. **Wire the `is_staging` flag** — `app()->instance('is_staging', true)` around
   the staging build, `app()->forgetInstance('is_staging')` after. This arms the
   guard that #28 Decision 15 specified and that is currently dead code. With it
   armed, staging writes fire the observer but the observer no-ops, exactly as
   #28 Decision 15 intended.

3. **Embeddings batch-queued in the rebuild phase post-swap**, as #28 Decision
   15 already specifies: after publish, iterate
   `<product>.search_projections WHERE embedding_state='pending'`, dispatch
   `EmbeddingJob` per row, wait for drain. No per-row dispatch during staging;
   no raw inserts at all.

This honors #28 Decision 7, #28 Decision 15, #29 Decision 2 (as written), and
#32's trigger+observer model — all four, intact.

---

## 4. §2. Deviation 1 — Abandoning the shadow-schema-swap

### 4.1. The recorded decision

**#28 Decision 3** — _"Staging shape: shadow schema swap. Publish =
`BEGIN; DROP SCHEMA <product> CASCADE; ALTER SCHEMA <product>_staging RENAME TO
<product>; COMMIT;` — two-statement atomic, brief lock window."_

**#28 Decision 2** — _"Unified pipeline; first-import and reset share code path.
Every invocation is `DROP SCHEMA CASCADE → CREATE SCHEMA → re-import`."_

**ADR 100308** (Shadow-Schema Import Pipeline) is the architectural record, with
its own Decision 34 already flagging: _"Foreign key relationships from shared
infrastructure to product-domain tables (e.g. search documents) must be handled
carefully across schema swaps."_

### 4.2. The plan deviation

**Phase A, Task A1** — remove `DROP SCHEMA IF EXISTS chinook CASCADE;` from the
schema-migration `up()` methods. **Phase A, Task A3 / Phase F, Task F1** —
replace the swap with staging-only load + truncate-and-reload of the live domain
tables via the transform layer.

### 4.3. Why the plan deviates

The original premise — "only per-product objects are dropped by the CASCADE" —
was invalidated when T9 added the `public.product_portfolio_snapshots` view (a
`public` view depending on `chinook.artists` etc.) and the search-projection
objects (living inside the product schemas). CASCADE now traverses back into
`public` and drops the view.

### 4.4. A. What breaks if we ratify the deviation

**A.1 — #28 Decisions 2, 3, and 12 all assume the swap.** Atomicity
(two-statement tx, brief lock), the unification of first-import and reset
("every invocation is `DROP SCHEMA CASCADE → CREATE SCHEMA → re-import`"), and
the failure contract (pre-publish clean; post-publish `recovering`) are all
built on the swap. Truncate-and-reload has no atomic publish step, no shadow to
discard on failure, and no "new state" to recover toward.

**A.2 — The failure contract (#28 Decision 12) is structurally broken.** Under
the swap: pre-publish failure → drop shadow, live untouched, status `failed`.
Post-publish failure → live is the new state, status `recovering`. Under
truncate-and-reload there is no "pre-publish" — the live schema is being
mutated in place from the first `TRUNCATE`. A mid-transform failure leaves the
live schema half-truncated, with no shadow to fall back to. The `recovering`
status ("live schema is the new state; product unavailable") has no meaning
when there is no clean new state. Recovery degenerates to "re-run the whole
import" — which is exactly the un-recoverable case #28 Decision 12 was designed
to avoid.

**A.3 — Invariant verification (#28 Decision 10) is re-aimed at live.**
Invariants 3–7 were designed to gate the _staging_ schema before the atomic
swap — a failing invariant means "don't publish," live stays untouched. Under
truncate-and-reload, invariants run against the live schema _after_ it has
already been truncated and reloaded. A failing invariant leaves the live schema
in the failed state; the gate becomes post-hoc detection rather than
pre-publish prevention. The safety property ("a failed validation never touches
live") is lost.

**A.4 — ADR 100308 is the foundation being superseded.** Ratifying the
abandonment means superseding ADR 100308 (Accepted, dated 2026-07-25). Its
positive consequences — near-zero downtime, full isolation, atomic roll-forward,
independent per-product resets — all depend on the swap. The tradeoff ADR 100308
_did_ flag (Decision 34: cross-schema FKs need care) is the exact issue driving
the deviation; it is a known, bounded caveat, not a structural flaw (see §B).

**A.5 — "First-import = reset" unification (#28 Decision 2) dissolves.** That
unification depends on both operations ending in the same swap. Splitting them
(truncate for import, swap for reset) reintroduces the two-code-path complexity
#28 Decision 2 explicitly eliminated.

### 4.5. B. What breaks if we revert (keep the swap)

**B.1 — The `product_portfolio_snapshots` view IS dropped by the CASCADE — and
is trivially recreated.** This is the deviation's entire motivation. It is real:
`DROP SCHEMA chinook CASCADE` does drop the `public` view that depends on
`chinook.*`. But the view is a single DDL object in `public`, recreated by one
statement:

```sql
CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS
  SELECT 'chinook' AS product, count(*) AS artists FROM chinook.artists
  UNION ALL ...;
```

This is **exactly** the caveat ADR 100308 Decision 34 raised ("cross-schema FKs
must be handled carefully across schema swaps"). It is a known, bounded,
one-statement cost — not a reason to abandon the swap. The recreate belongs as a
post-swap step in the publish phase, alongside the existing `ANALYZE` (#28
Decision 13). Because the view is in `public` and its definition references
`<product>.*` by name, it is stable across re-imports; it does not need to be
re-derived.

**B.2 — The search-projection objects are recreated by migration replay.** This
is the swap's _purpose_, not a cost. The swap is `DROP SCHEMA <product> CASCADE`
then `CREATE SCHEMA <product>` + replaying the product's migrations, which
include the `210001`/`211001`/`212001` migrations that create
`<product>.search_projections`, its GIN/HNSW indexes, the trigger functions,
and the per-table triggers. They are rebuilt automatically and atomically with
the schema. (This is the contrast with the current bug: today the view is NOT
rebuilt, because the `213000` _migration_ records itself as "ran" and
`migrate` won't re-run it. Under the swap, the view is recreated by an explicit
post-swap step, not by migration — so the "migrations table lied" trap does not
apply.)

**B.3 — Reset Recovery (#28 Decision 12) works as designed.** With the swap,
the failure contract is intact: pre-publish failure drops the shadow, live
untouched; post-publish failure leaves the new live state, status `recovering`,
operator retries. The recovery runbook (#29 Decision 5) keys off this.

**B.4 — Cross-schema FKs from `public` to `<product>.*`.** Beyond the portfolio
view, are there other `public` objects depending on product schemas? The
`source_identities` table (#25) lives in `public` but does _not_ FK to product
tables (it stores `domain_id` as a loose UUID, by design — it must survive the
drop). So the portfolio view is the _only_ cross-schema dependent. Confirmed
scope: one view.

### 4.6. C. Third options considered

**C.1 — Hybrid: swap for Reset, truncate for Import.** Since import and reset
are conceptually different (#28 Decision 2 unified them as "first-import =
reset"), one could imagine keeping the swap for resets but using
truncate-and-reload for imports. Rejected: #28 Decision 2 deliberately unified
them to avoid exactly this split. Maintaining two publish paths — one atomic,
one not — reintroduces the complexity #28 eliminated and creates a
probabilistic correctness gap (the two paths can drift). The unification is a
load-bearing decision; splitting it is a larger change than ratifying either
deviation alone.

**C.2 — Move the portfolio view into a schema that survives.** E.g. put it in a
`reporting` schema. Rejected as over-engineering: the view is a `public`
object by convention, and the recreate cost is one statement. Moving it adds a
new schema to manage for no gain over a post-swap `CREATE OR REPLACE VIEW`.

**C.3 — Make the view resilient to its dependencies being dropped.** Postgres
views are not resilient by design — a view depends on its underlying tables, and
dropping the tables drops the view. There is no "lazy view" option. This is not
a real lever.

### 4.7. D. Recommendation: Revert

Keep the shadow-swap (#28 Decision 3, ADR 100308). Add a post-swap
`CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS …` step to the
publish phase — one statement, recreating the single cross-schema dependent.
Search-projection objects rebuild automatically via migration replay (already
the swap's design). This:

- Honors #28 Decisions 2, 3, 10, 12 intact.
- Honors ADR 100308 and its Decision 34 caveat (now addressed concretely).
- Resolves the view-destruction bug as a small, documented publish-phase step
  rather than a foundational pipeline change.
- Keeps the failure contract, the invariants-as-gate, and first-import=reset
  unification all working as recorded.

The recreate step should be added to the plan's publish phase and to the
`213000` migration's `up()` (so a fresh `migrate:fresh` also creates it), with a
code comment cross-referencing ADR 100308 Decision 34.

---

## 5. §3. Required revisions to the plan

Reverting both deviations means the plan
(`docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`) must be
restructured. This analysis does not rewrite the plan — that is a follow-up —
but records the required changes so the revision is scoped.

### 5.1. Phase A — restructured

- **Keep** `DROP SCHEMA … CASCADE` in the schema-migration `down()` methods
  (already correct) **and** in the importers' publish step. Do **not** remove it
  from `up()` either — the plan's Task A1 removal was the deviation; revert it.
- **Add** a post-swap `CREATE OR REPLACE VIEW public.product_portfolio_snapshots`
  step to each importer's publish phase (after `ALTER SCHEMA … RENAME`),
  guarded so it runs once the renamed schema exists. Also ensure `213000`
  creates the view on fresh installs.
- **The schema-preservation regression test (Task A2)** stays, but its
  assertion inverts: instead of "import must not destroy the view," it becomes
  "import recreates the view during publish." Same coverage, adjusted
  expectation.

### 5.2. Phase B — transform writes through Eloquent, to a migration-built staging

**Critical staging-shape correction.** #28 Decision 4 specified staging as
_migration-built_ (schema-name-parameterised migrations replayed against
`<product>_staging`), not dump-loaded. The current code loads the upstream dump
straight into staging (giving it _upstream_ shapes — integer PKs), which is the
root reason the transform was ever needed. Under the revert, staging must be
built by app migrations so it has the app's UUID shapes **and the
search-projection triggers** (created by the `210001`/`211001`/`212001`
migrations). The upstream dump is read separately by the `SourceReader` into a
scratch location and transformed _into_ the migration-built staging tables.

So the revert uses three schemas per product:

- `<product>_source` (scratch) — upstream dump loaded here, upstream shapes; the
  `SourceReader` yields rows from these tables.
- `<product>_staging` — app migrations replayed here (`product:stage <product>`
  per #28 Decision 4); UUID shapes + triggers. The transform writes here via
  Eloquent. Triggers fire during staging writes, populating
  `<product>_staging.search_projections` with `embedding_state='pending'` (per
  #28 Decision 15).
- `<product>` — live schema; the swap target.

The `TableMapper`'s `stagingTable()` (read source) points at `<product>_source`;
its write target is the migration-built `<product>_staging.<domain_table>` via
Eloquent. On publish: `DROP SCHEMA <product> CASCADE; ALTER SCHEMA
<product>_staging RENAME TO <product>;` then `DROP SCHEMA <product>_source`.
The renamed-live schema arrives with its projection rows already populated;
the rebuild phase then drains embeddings.

**Concretely:**

- **`TableMapper::load()`** changes its write call from
  `DB::table($this->domainTable())->insert($domain)` to Eloquent model
  instantiation + `save()`, targeting **staging-resolved** model subclasses
  (e.g. `Chinook\Staging\Artist`) bound to `<product>_staging` and _not_
  carrying `BelongsToProductDomain`.
- The `SourceIdentityRegistry` verification (Task B1) stands — it is already
  safe during a run (`SourceIdentity` has no domain trait).
- The `SelfReferentialMapper` two-pass logic (Task B3) translates directly to
  Eloquent: insert with `reports_to = null`, then `$model->update(...)`.
- **Wire `is_staging`** (`app()->instance('is_staging', true)` for the
  staging-build scope, `forgetInstance` after) so #28 Decision 15's observer
  suppression actually arms — staging Eloquent writes fire the observer, which
  then no-ops instead of dispatching per-row `EmbeddingJob`s.

### 5.3. Phases C–E — mapper families

- The per-product mapper families (Chinook/Northwind/Pagila) largely survive —
  the declarative column/FK mapping is independent of the write mechanism. The
  concrete change is the write call in the base `TableMapper`, already covered
  in Phase B.

### 5.4. Phase F — wiring

- The importer refactor (Task F1) restores the shadow-swap publish step and
  adds the post-swap view recreate.
- The rebuild phase (post-publish) gains the explicit embedding-drain step:
  iterate `<product>.search_projections WHERE embedding_state='pending'`,
  dispatch `EmbeddingJob` per row, wait for drain (#28 Decision 15). This step
  is **required** for Invariant 8 to be passable.

### 5.5. Open questions for the revision

1. **Staging-model shape.** The staging-resolved subclass pattern adds a new
   namespace (`App\Domain\<Product>\Staging\`). Whether this lives alongside the
   existing models, is generated per-run, or is expressed via a `setTable`
   indirection on the live model (with the trait scope-narrowed to non-staging
   calls) is an implementation detail for the revised plan. The subclass shape
   is the cleanest because it drops the trait outright; `setTable` alone does
   not (the trait still boots and gates).
2. **Three-schema lifecycle.** The `<product>_source` / `<product>_staging` /
   `<product>` dance is more moving parts than the current two-schema code. The
   revised plan should specify their creation, cleanup (on success and on
   failure), and the `product:stage` command (#28 Decision 4) explicitly.
3. **#29 arch-rule refinement.** The `BelongsToProductDomain` mandate scopes to
   live-schema models; staging subclasses are explicitly exempt — making #29
   Decision 2's stated exemption an enforced rule rather than a stated intent.
   This refinement is ADR-worthy (see §4).

---

## 6. §4. ADR and follow-up work (filed, not executed here)

Per the operator's decision, this session produces analysis only. The following
are filed as follow-ups:

1. **Superseding/clarifying ADRs.** Because both deviations are _reverted_
   rather than ratified, no decision is superseded. But two ADRs are worth
   adding as **clarifications** (not supersessions), because the analysis
   surfaced gaps the original records left implicit:
    - A clarification to **ADR 100308** (or a new companion ADR): the
      post-swap recreate of cross-schema `public` dependents (the portfolio
      view) is a required publish-phase step. This makes ADR 100308 Decision 34's
      caveat concrete.
    - A clarification to the **#29-mandated trait arch rule**: the
      `BelongsToProductDomain` mandate scopes to _live-schema_ models; staging
      subclasses (writing to `<product>_staging`) are explicitly exempt, making
      #29 Decision 2's stated exemption an enforced rule rather than a stated
      intent.

2. **Plan revision.** Restructure
   `docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md` per
   §3 above.

3. **Issue #81** (behavioral-compliance check) remains the prerequisite blocker
   for the transform phases. The revert does not change that ordering.

These should be tracked via the project's issue tracker (bd / GitHub) rather
than left as prose.

---

## 7. §5. The eight Baseline Invariants under each path

#28 Decision 10 specified eight invariants gating publish/verify. The handoff
asked whether they hold under truncate-and-reload. They do not — which is
another reason to revert. Summary:

| Inv | Name                              | Holds under shadow-swap (revert)?  | Holds under truncate-and-reload (deviation)?                      |
| --- | --------------------------------- | ---------------------------------- | ----------------------------------------------------------------- |
| 1   | Source artifact SHA-256           | ✅ (preflight, path-independent)   | ✅ (path-independent)                                             |
| 2   | Per-table row counts              | ✅ (validated on staging pre-swap) | ⚠️ (validated on live post-truncate — weaker)                     |
| 3   | Registry coverage                 | ✅ (staging pre-swap)              | ⚠️ (live post-truncate)                                           |
| 4   | FK integrity                      | ✅ (staging pre-swap)              | ⚠️ (live post-truncate; and DEFERRABLE circle relies on tx)       |
| 5   | Normalisation anomalies           | ✅ (staging pre-swap)              | ⚠️ (live post-truncate)                                           |
| 6   | Product isolation                 | ✅ (staging pre-swap)              | ✅ (path-independent)                                             |
| 7   | Derived projection populated      | ✅ (staging pre-swap)              | ✅ (trigger fires either way)                                     |
| 8   | Embedding lifecycle (`pending`=0) | ✅ (rebuild drains post-swap)      | ❌ (**never** — raw inserts leave all rows `pending`; see §1.A.1) |

The shadow-swap column is green across the board; truncate-and-reload degrades
Invariants 2–5 from pre-publish gate to post-hoc detection, and breaks
Invariant 8 outright under the raw-insert deviation.

---

## 8. References

- **Decisions of record:** [#28](https://github.com/s-a-c/samples-20260717/issues/28)
  (pipeline + invariants), [#29](https://github.com/s-a-c/samples-20260717/issues/29)
  (Reset Window + trait), [#41](https://github.com/s-a-c/samples-20260717/issues/41)
  (per-product schemas), [#32](https://github.com/s-a-c/samples-20260717/issues/32)
  (search projection integrity).
- **ADR 100308** — Shadow-Schema Import Pipeline (the swap's architectural
  record).
- **Plan** — `docs/superpowers/plans/2026-08-05-import-cascade-fix-and-transform.md`.
- **Handoff** — `docs/superpowers/handoffs/2026-08-05-import-decision-deviations.md`.
- **Code cited:**
  `app/Traits/BelongsToProductDomain.php`,
  `app/Observers/Tier1SourceObserver.php`,
  `app/Jobs/EmbeddingJob.php`,
  `app/Services/ProductReset/ResetWindow.php`,
  `app/Services/ProductImport/ProductImportPipeline.php`,
  `database/migrations/chinook/*210001*` (search-projection triggers).
