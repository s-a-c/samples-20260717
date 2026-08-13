---
title: "Wayfinder #15 — Handover (2026-07-22)"
description: "Continue wayfinder map #15 — Wayfinder — Samples Implementation."
type: handoff
tags: \[handoff, handoffs, wayfinder, "15"]
updated: 2026-07-30
---

# Wayfinder #15 — Handover (2026-07-22)

Continue wayfinder map #15 — Wayfinder — Samples Implementation.

- **Map:** https://github.com/s-a-c/samples-20260717/issues/15
- **Bead:** `samples-20260717-1784648472636-14-eac5a270` (bd mirror of #15)

## What this map is

A plan-only wayfinder map whose destination is a production-ready Laravel 13 + PHP 8.5 application presenting Chinook, Northwind, and Pagila as three Sample Products, passing all eight Acceptance Stages from #11, the Two-Environment Operational Gate, Larastan at `level: max`, comprehensive Pest coverage, and the Implementation-Readiness Dossier. Per the map's own Notes: _"Plan-only mode — each ticket resolves ONE decision; execution is handed off once the way is clear. No ticket carries implementation into itself."_

## State at handover (2026-07-22)

All fifteen planning decisions are resolved — the twelve from map #1 plus three implementation-shape decisions. See the map's "Decisions so far" section for the indexed list with links. The way is clear for Stages 1–4. Stage 5–7 detailed tickets graduate from fog as earlier stages land.

Three execution beads are filed under the map in bd (technically violating the plan-only mode, but they're the concrete unblock for Stage 1):

| Bead | Title                                                 | Status                                        |
| ---- | ----------------------------------------------------- | --------------------------------------------- |
| `.1` | Edit phpstan.neon to level: max + add tests/ to paths | in_progress, **BLOCKED**                      |
| `.2` | Decide Larastan max-violation gate policy             | open, P1, **FRONTIER** — blocks `.1` and `.3` |
| `.3` | Fix 36 nullable-safety violations                     | open, P2, blocked by `.2`                     |

## Immediate next action

Resolve `.2` — the Larastan gate-policy decision. It blocks `.1` (which is already edited and committed at `2ab2079` but can't be closed until verification passes) and `.3` (the nullable-safety fixes). `.2` is a grilling ticket: run it with `/grilling` + `/domain-modeling`.

The policy constraint from #17's resolution: _"strict from day 0 with shrinking `ignoreErrors` carve-outs, no global baseline file — each carve-out MUST cite the bd execution ticket that will fill the namespace."_ A generated baseline (`phpstan-baseline.neon`) is **forbidden** by this precedent.

The 143 violations at `level: max` break down as:

| Count | Identifier                                | Nature                                                        |
| ----: | ----------------------------------------- | ------------------------------------------------------------- |
|    61 | `staticMethod.dynamicCall`                | strict-rules; stylistic — `Assert::` and `Builder::` in tests |
|    36 | `property.nonObject` + `method.nonObject` | real nullable safety — `Auth::user()` etc.                    |
|    17 | `booleanNot` / `condNotBoolean`           | strict-rules                                                  |
|     7 | `typeCoverage.paramTypeCoverage`          | peststan — 94.5% < 99%                                        |
|    22 | misc across 9 identifiers                 | mixed                                                         |

## Critical environment facts (discovered this session)

1. **PHPStan 2.2.5 has NO `--threads` option** (auto-parallelises via pcntl). The `--threads=1` in some acceptance text is obsolete.

2. **PHPStan silently exits 1 with NO output whenever Xdebug is loaded** (always on Herd — `99-xdebug.ini` sets `xdebug.mode=debug,develop`). This masks ALL real analysis output. Working verification command on macOS Herd:

    ```
    XDEBUG_MODE=off php -d xdebug.mode=off vendor/phpstan/phpstan/phpstan analyse --memory-limit=512M --no-progress
    ```

3. **Duplicate `phpstan.neon` includes** (larastan + carbon, both auto-loaded by `phpstan/extension-installer`) were removed in commit `2ab2079`. They were causing phpstan to bail before any analysis ran.

4. **"Module herd already loaded" warning is cosmetic** — herd-ext is compiled into Herd's PHP binary AND loaded again via `php.ini`. Does not affect exit code.

## Skills to load at session start

`wayfinder`, `grilling`, `domain-modeling`, `laravel-best-practices`, `pest-testing`. Run `bd prime` for beads context. Read `CONTEXT.md` for the domain glossary (Sample Product, Product Domain, Product Import, Product Reset, Reset Window, Acceptance Gate, Acceptance Stage, etc.).

## Repository

`/Users/s-a-c/Herd/samples-20260717` — Laravel 13 + PHP 8.5, Herd-managed PostgreSQL 18 + pgvector on `127.0.0.1:5437`. Branch: `main`. Last commit: `a91c5b0` (chore(workspace): migrate Siyuan + refresh deps).
