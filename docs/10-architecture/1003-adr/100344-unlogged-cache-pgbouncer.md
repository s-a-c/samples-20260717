---
title: "ADR 0023: Unlogged Cache Tables + Local PgBouncer"
description: "PostgreSQL-only performance hardening: UNLOGGED cache tables and transaction-pooled local connections"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, architecture, postgres, caching, connections]
created: 2026-08-23
updated: 2026-08-23
---
# ADR 0023: Unlogged Cache Tables + Local PgBouncer

**Status:** Accepted
**Date:** 2026-08-23

**Context:** The application runs its cache, queue, and session stores on the database driver against a single PostgreSQL 18 (Herd build, `127.0.0.1:5437`) — Redis/Valkey appear only as vestigial `.env` vars with no consumer in `composer.json`. Two costs come with that simplicity: every ephemeral cache write is written twice (table + WAL), and PostgreSQL spawns a process per connection while the web runtime, queue workers, and the Pest suite each hold their own. The cache-usage audit (issue #115) found four cache consumers, all ephemeral and self-rebuilding: a 30 s portfolio widget snapshot, 60 s Fortify rate-limit windows, a 24 h spatie permission cache, and an unused `cache_locks` table. Nothing in the cache must survive a crash.

**Decision:**

1. **UNLOGGED cache tables.** A dedicated migration (`2026_08_22_233147_set_cache_tables_unlogged`) runs `ALTER TABLE … SET UNLOGGED` on `cache` and `cache_locks` only, bypassing WAL for cache writes. A new migration — not an edit of the original create migration — so already-provisioned databases flip in place and fresh installs converge. Queue tables (`jobs`, `job_batches`, `failed_jobs`) and `sessions` keep full WAL durability: queue loss on crash would silently drop import and embedding work. `tests/Feature/Postgres/CacheTablesUnloggedTest.php` asserts the `relpersistence` flags so drift is caught by the suite.
2. **Local PgBouncer, transaction pooling.** PgBouncer 1.25.2 (brew service) listens on `127.0.0.1:6432` and forwards to Herd Postgres on 5437: `pool_mode=transaction`, `max_prepared_statements=200`, `track_extra_parameters=IntervalStyle,search_path` (Laravel's connector issues `SET search_path` on connect), wildcard database mapping so the `_testing` and parallel `_test_N` databases pool too. Auth is `scram-sha-256` against a `userlist.txt` (chmod 600). All local traffic — Herd-FPM web, queue workers, and the full Pest suite — routes through the pooler.
3. **Laravel pooled + direct split.** `config/database.php` adds `pooled` and `direct` keys to the pgsql connection (Laravel 13 native). The pooled path forces emulated prepares (verified: zero server-side prepares, `RETURNING` works); migrations, schema dumps, `db:wipe`, and DDL auto-route via `pgsql::direct` to 5437, bypassing the pooler. `.env`, `.env.testing`, and `phpunit.xml` carry a consistent set: `DB_PORT=6432`, `DB_POOLED=true`, `DB_DIRECT_HOST=127.0.0.1`, `DB_DIRECT_PORT=5437`.
4. **Test database ownership.** The Pest suite owns `samples_20260717_testing`, pinned in `phpunit.xml` (no `force`, so CI's real environment wins); it is self-provisioning via `migrate:fresh` (extensions and unlogged flags are migration-managed). CI (GitHub Actions) stays direct on its own service container — `DB_POOLED=false` is pinned in all three workflows.

**Consequences:**

- **Positive:** Cache writes skip WAL; crash recovery truncates unlogged tables to empty, which the audit shows is harmless (every consumer rebuilds on next read).
- **Positive:** Many short-lived client connections share a few server processes; idle queue workers hold no server connection.
- **Positive:** The suite proves the pooled path on every run (588/588 through 6432), so the pooler cannot silently rot.
- **Tradeoff:** A crash mid-flight resets rate-limit windows and logs users out of nothing (sessions stay logged tables) — accepted.
- **Tradeoff:** scram through the pooler requires the DB password literally in gitignored `.env` and in `userlist.txt` — Herd-FPM cannot expand `${VAR}` indirection, which trust auth on 5437 had previously masked. Exposure is equal either way (same plaintext, same file permissions); single-user machine.
- **Tradeoff:** `getConfig('port')` on `pgsql::direct` reports the base (pooled) config — the framework attaches a separate direct PDO to a shared connection. Verify bypass behavior with PgBouncer login-deltas, not config readouts.

**Operations:**

- PgBouncer ini: `/opt/homebrew/etc/pgbouncer.ini`; userlist: `/opt/homebrew/etc/pgbouncer/userlist.txt` (chmod 600, rewrite when the DB password rotates). Service: `brew services start|stop|restart pgbouncer`. Log: `/opt/homebrew/var/log/pgbouncer.log`.
- Bypass the pooler (dumps, psql, emergencies): connect directly on 5437. Admin console: `psql -p 6432 pgbouncer -c 'SHOW POOLS'` (user `s-a-c` is an admin_user).
- The full Pest suite needs `memory_limit` ≥ ~512M (default 128M exhausts during Blade/Blaze compilation).
- `storage/logs/laravel.log` grows unbounded (825 MB observed at adoption time); truncate periodically until log rotation is configured.
- After any full-suite run on a machine predating the test-DB pin, the dev database may have been rebuilt — re-run `product:import` to restore sample data.

**Related:**

- [ADR 0018: Acceptance & Operational Gates](100334-acceptance-and-operational-gates.md)
- [ADR 0021: Single Postgres Test Suite](100337-single-postgres-test-suite.md)
- Research: issues #115 (cache crash-loss audit) and #117 (PgBouncer/Laravel compatibility), branches `research/unlogged-cache-audit`, `research/pgbouncer-laravel-compat`
- Decision map: [Wayfinder — Postgres Cache Hardening](https://github.com/s-a-c/samples-20260717/issues/114)
