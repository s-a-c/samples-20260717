# Wayfinder handoff — next session

**Date:** 2026-07-21
**Map:** [s-a-c/samples-20260717#15](https://github.com/s-a-c/samples-20260717/issues/15) (Wayfinder — Samples Implementation)
**Tracker state at handoff:** bd synced 1:1 with gh — 23 open beads ↔ 23 open gh issues, 25 closed. Run `bd stats` to verify; `bd ready` for the work surface.

---

## What happened recently

**Postgres-pivot trio CLOSED** in three consecutive sessions (2026-07-20):

| Ticket                                                                  | Decision                                                                                                                                                                                                                                                                                                                                                                                                            | SAGE fact  |
| ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- |
| [#40](https://github.com/s-a-c/samples-20260717/issues/40) — Stack      | Herd-managed PG 18.x + pgvector 0.8.x + unaccent 1.1 + pg_trgm 1.6 (Herd-bundled pin policy). HNSW defaults (`m=16`, `ef_construction=64`, `ef_search=40`) for 1,536-dim OpenAI `text-embedding-3-small`. tsvector `english` + `unaccent` via custom `en_unaccent` config. `ts_rank_cd` with ABCD field weights.                                                                                                    | `34a6160e` |
| [#41](https://github.com/s-a-c/samples-20260717/issues/41) — Schema     | Three product schemas (`chinook`/`northwind`/`pagila`) + `public` for shared infrastructure. Drop product prefix from table names (partial supersession of #6). Single `pgsql` connection with `search_path=public`; schema-qualify every product reference. Per-product `search_projections` tables (drops atomically with `DROP SCHEMA CASCADE` during reset). Infrastructure + `en_unaccent` config in `public`. | `34e3bd40` |
| [#42](https://github.com/s-a-c/samples-20260717/issues/42) — Extensions | Single early migration declares `vector` + `unaccent` + `pg_trgm` + `en_unaccent` config. `down()` no-op. CI: `pgvector/pgvector:pg18` Docker service. Verification: Pest test + `pgsql:check` command + docs. All 14 SQLite-specific CONTEXT.md terms dissolve.                                                                                                                                                    | `e50f71ea` |

**Operational finding (from #40):** Herd-managed Postgres on `:5437` had WAL corruption at grilling start. Re-initialized losslessly via `herd services:delete Herd_PostgreSQL --force && herd services:create postgresql --name=Herd_PostgreSQL --port=5437 --service-version=18`. New data dir: `~/Library/Application Support/Herd/config/services/87D2AEDE-3450-4FCA-B025-D9122E1214C8/`. If WAL corruption recurs, same pattern is the recovery.

**Beads dedup (this session):** Bd had 60 open beads with ~28 duplicate pairs from a prior buggy `bd import`. Closed 14 stale-open beads (gh-closed issues) + deleted 23 duplicates (one per gh-open pair). Result: 1:1 sync, 23 open ↔ 23 open. JSONL export regenerated; old file preserved at `.beads/issues.jsonl.stale-20260721`.

---

## Recommended next ticket

**[#32 — Search Projection column shape](https://github.com/s-a-c/samples-20260717/issues/32)** · bd: `samples-20260717-7ie`

Direct downstream of the trio. Decides the PostgreSQL schema for per-product `search_projections` tables (placement already fixed by #41). This unblocks #33 (Embedding Profile impl) and #34 (RRF + Federated Search).

### What #32 decides

- `tsvector` column: `GENERATED ALWAYS AS (to_tsvector('en_unaccent', ...)) STORED` vs trigger-updated vs application-maintained
- GIN index parameters on the tsvector column
- `vector(1536)` column (dimensionality from #8)
- HNSW index parameters (#40 already set `m=16`, `ef_construction=64`, `ef_search=40` — confirm or refine)
- Whether to retain the after-commit verification step from the original FTS5+vec0 design, or drop it as unnecessary under Postgres transactional DDL/DML
- Exact column names + tsvector weight materialisation (ABCD from #40: D=title/name, C=description, B=category, A=identifier)
- `search_id` bridge to UUIDv7 Domain Identity (#6 stands)
- Transactional consistency model (both tsvector AND vector updates are transactional under Postgres — the SQLite-era after-commit workaround may no longer be required for correctness, only for embedding latency)

### Out of scope (do NOT re-litigate)

- Stack / schema / extensions (#40/#41/#42 trio — closed)
- Embedding Profile provider/model (#8 — OpenAI `text-embedding-3-small` @ 1,536 dims)
- RRF + Federated Search query shape (#34)
- Embedding pipeline mechanics / queue job / after-commit listener (#33)
- Search Document content per entity (#31)

---

## Orientation reading list (load in parallel before grilling)

```
gh issue view 15 --repo s-a-c/samples-20260717                  # map
gh issue view 32 --repo s-a-c/samples-20260717                  # the ticket
gh issue view 40 --repo s-a-c/samples-20260717 --comments       # stack resolution
gh issue view 41 --repo s-a-c/samples-20260717 --comments       # schema resolution
gh issue view 42 --repo s-a-c/samples-20260717 --comments       # extensions resolution
gh issue view 8  --repo s-a-c/samples-20260717 --comments       # search tiers + embedding profile
gh issue view 6  --repo s-a-c/samples-20260717 --comments       # schema shape (UUIDv7, source_id)
gh issue view 7  --repo s-a-c/samples-20260717 --comments       # import + reset semantics
```

Read `CONTEXT.md` at repo root — focus on *Search Document*, *Search Surface*, *Search Tier*, *Search Projection*, *Embedding Profile*, *Hybrid Retrieval*.

SAGE recall:
```
sage_recall query="Postgres search projection tsvector HNSW map 15"
```
Should surface facts `34a6160e` (#40), `34e3bd40` (#41), `e50f71ea` (#42).

---

## Local prereqs (verify with one command each — surface failures BEFORE grilling)

```bash
# Herd Postgres on :5437 healthy
PGPASSWORD= psql -h 127.0.0.1 -p 5437 -U root -d laravel -c "SELECT version();"

# Recovery if WAL-corrupted:
# herd services:delete Herd_PostgreSQL --force && \
# herd services:create postgresql --name=Herd_PostgreSQL --port=5437 --service-version=18

# Extensions available (installed after the extensions migration runs)
PGPASSWORD= psql -h 127.0.0.1 -p 5437 -U root -d laravel -c \
  "SELECT name, default_version FROM pg_available_extensions WHERE name IN ('vector','unaccent','pg_trgm');"

# HNSW access method registered (after pgvector install)
PGPASSWORD= psql -h 127.0.0.1 -p 5437 -U root -d laravel -c \
  "SELECT amname FROM pg_am WHERE amname = 'hnsw';"
```

---

## Mode

wayfinder "Work through the Map" — HITL grilling ticket. Work WITH the operator one question at a time, breadth-first then depth. Use the `question` tool for multi-choice decisions; let the operator pick or propose alternatives.

## Docs to consult

- Context7 `/pgvector/pgvector` — query `"tsvector generated column STORED"` and `"HNSW index parameters m ef_construction"`
- Context7 `/websites/postgresql_18` — query `"generated column tsvector"` and `"GIN index tsvector"`
- Context7 `/laravel/docs` — query `"postgres generated column schema blueprint"`
- Verify `GENERATED ALWAYS AS ... STORED` syntax against PG18 docs

---

## Stop protocol (wayfinder skill)

1. Post resolution comment on the ticket with the decision
2. Close the ticket (gh + bd — see "bd maintenance" below)
3. Append a one-line gist + link to map #15's "Decisions so far" section (fetch body, edit, set via `gh issue edit 15 --body-file`)
4. Note unblocks on dependent tickets via gh comments if any
5. Graduate any newly-specifiable fog from map #15's "Not yet specified"
6. Record the decision as a SAGE fact (`type=fact`, `domain=samples-20260717`)
7. **Stop** — never resolve more than one ticket per session

---

## Session mechanics

- **Working dir:** `/Users/s-a-c/Herd/samples-20260717`
- **Repo:** `s-a-c/samples-20260717` (GitHub private — use `gh` CLI)
- **Tracker:** bd is the primary surface; gh is authoritative. Use `bd ready` to find work, `bd show <id>` for details, `bd update <id> --claim` to claim, `bd close <id>` to complete. Bd's `external_ref` (e.g. `gh-32`) cross-references the gh issue.
- **SAGE:** `direnv` auto-loads `.env.sage` on `cd` into the repo → identity becomes `project/samples-20260717`. Cite memories as `sage://project/samples-20260717/...`.
- **Per-turn SAGE cycle:** call `sage_turn` at start (claim + topic + observation), once mid-session if long, once at end (resolution summary). Warning fires after 2 missed turns.
- **Git:** conservative profile — propose commands, don't auto-commit/push. No code should change in a wayfinder session (only GitHub Issues).
- **GEMINI_API_KEY** rotated 2026-07-19; do not echo env in commits/comments.
- **Long resolution comments:** write to `/tmp/opencode/<file>.md`, post via `gh issue comment N --body-file`.
- **Chinese content:** use Node.js `fs.writeFileSync` (Edit/Write tools mojibake Chinese) — English-only handoffs like this one are fine via Write.
- **Question tool:** use for multi-choice decisions; operator can pick or propose alternatives.
- **Three prior tickets** (#40/#41/#42) followed this protocol cleanly — same pattern works.

---

## bd maintenance (re-sync if drift reappears)

The known duplication bug from `gh issue list | jq | bd import` (SAGE memory `adfd867b`) creates duplicate beads rather than upserting. If duplicates reappear:

```bash
# 1. Compare: gh state vs bd state
gh issue list --repo s-a-c/samples-20260717 --state all --limit 100 --json number,state > /tmp/gh-state.json
bd list --json > /tmp/bd-state.json

# 2. Group bd beads by external_ref; identify (a) dups, (b) stale-open for gh-closed
python3 << 'EOF'
import json
from collections import defaultdict
gh = {i['number']: i['state'] for i in json.load(open('/tmp/gh-state.json'))}
bd = json.load(open('/tmp/bd-state.json'))
groups = defaultdict(list)
for b in bd: groups[b.get('external_ref','')].append(b)
for ref, beads in sorted(groups.items()):
    if not ref.startswith('gh-'): continue
    num = int(ref.split('-')[1])
    gh_state = gh.get(num, 'MISSING').upper()
    if gh_state == 'CLOSED':
        for b in beads:
            if b.get('status') == 'open':
                print(f"CLOSE {b['id']} ({ref}) — gh issue closed")
    elif len(beads) > 1:
        keeper = sorted(beads, key=lambda b: b.get('created_at',''))[-1]
        for b in beads:
            if b['id'] != keeper['id']:
                print(f"DELETE {b['id']} (keep {keeper['id']} for {ref})")
EOF

# 3. Apply: bd close <ids...> --reason="..." ; bd delete <ids...> --force
# 4. Regenerate JSONL: bd export > .beads/issues.jsonl
```

---

## Alternatives (operator may substitute via `bd show`)

If #32 isn't the right next pick, all 22 other open tickets are unblocked and independent. Use `bd ready` to browse; `bd show <id>` for details. Notable options:

| bd id                  | gh # | Title                                                                  |
| ---------------------- | ---- | ---------------------------------------------------------------------- |
| `samples-20260717-7ie` | #32  | Search Projection column shape **(recommended)**                       |
| `samples-20260717-j64` | #33  | Embedding Profile implementation                                       |
| `samples-20260717-s5a` | #34  | Hybrid Retrieval (RRF) + Federated Search                              |
| `samples-20260717-3sm` | #24  | UUIDv7 implementation strategy                                         |
| `samples-20260717-zio` | #25  | Source Identity Registry survival-across-resets                        |
| `samples-20260717-sld` | #26  | Spatie Permission/Shield/Fortify co-existence                          |
| `samples-20260717-atr` | #16  | Filament 5 panel install order                                         |
| `samples-20260717-di9` | #17  | Test pyramid                                                           |
| `samples-20260717-a9v` | #18  | Larastan target level                                                  |
| `samples-20260717-2mv` | #27  | Re-verify upstream datasets                                            |
| `samples-20260717-oal` | #28  | Product Import pipeline                                                |
| `samples-20260717-5fb` | #29  | Product Reset semantics                                                |
| `samples-20260717-h3a` | #30  | Filament Resource generation                                           |
| `samples-20260717-dk6` | #31  | Search Document shape per entity                                       |
| `samples-20260717-f8e` | #35  | Portfolio Card architecture                                            |
| `samples-20260717-jo9` | #36  | Team Artefact schema                                                   |
| `samples-20260717-5zr` | #37  | Implementation-Readiness Dossier format                                |
| `samples-20260717-70l` | #38  | Documentation set                                                      |
| `samples-20260717-lo6` | #39  | Git branch/PR/dep-pinning strategy                                     |
| `samples-20260717-27t` | #20  | Verify sqlite-vec v0.1.9 assets (may be dissolvable post-pivot)        |
| `samples-20260717-thd` | #23  | Verify Herd SQLite extension mechanics (may be dissolvable post-pivot) |
| `samples-20260717-ejr` | #19  | Verify Filament 5 + Shield + Livewire co-installation                  |
| `samples-20260717-q87` | #15  | Wayfinder map itself (parent — not for resolution)                     |

**Note on #20 and #23:** These T2.1/T2.4 research tickets are what triggered the Postgres pivot. Their original deliverables (sqlite-vec asset verification, Herd SQLite extension mechanics) are now moot under Postgres. They may warrant closing as out-of-scope (like #21/#22 were) rather than execution — operator's call.

To swap: substitute the bd/gh number throughout; protocol is otherwise ticket-agnostic.

---

## Start here

```bash
cd /Users/s-a-c/Herd/samples-20260717

# 1. Verify bd state
bd stats          # expect: 23 open, 25 closed
bd ready          # 23 issues ready

# 2. Claim the ticket (gh + bd)
gh issue edit 32 --repo s-a-c/samples-20260717 --add-assignee @me
bd update samples-20260717-7ie --claim

# 3. SAGE turn — open the cycle
# (via sage_turn tool: topic="Search Projection column shape",
#  observation="loading #32 context; bd synced 1:1 with gh")

# 4. Read orientation list (parallel)

# 5. Verify local prereqs

# 6. Begin grilling the operator
```
