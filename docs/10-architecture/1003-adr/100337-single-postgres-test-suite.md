---
title: "ADR 0021: Single Postgres Test Suite"
description: "Accepted — amends the dual-suite clause of [ADR 0013 (Test Pyramid)](100326-test-pyramid.md) / [Wayfinder #17](https://github.com/s-a-c/samples-20260717/issues/17)."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0021", single]
created: 2026-07-28
updated: 2026-08-17
---
# ADR 0021: Single Postgres Test Suite

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Status](#1--status)
- [2. 📄 Context](#2--context)
- [3. 📄 Decision](#3--decision)
- [4. 📄 Consequences](#4--consequences)
- [5. 📄 References](#5--references)

</details>

---
## 1. 📄 Status

Accepted — amends the dual-suite clause of [ADR 0013 (Test Pyramid)](100326-test-pyramid.md) / [Wayfinder #17](https://github.com/s-a-c/samples-20260717/issues/17).

## 2. 📄 Context

ADR 0013 specified a **dual test suite**: the default `tests/Feature/*` on SQLite `:memory:`, and `tests/Feature/Postgres/*` on a `pgvector` service. After the Postgres pivot ([Wayfinder #40–#42](https://github.com/s-a-c/samples-20260717/issues/40)), the SQLite tier duplicated the schema migrations and offered no independent signal — SQLite is not a deployment target. The codebase already runs every test on Postgres (`phpunit.xml` `DB_CONNECTION=pgsql`; CI's `pgvector/pgvector:pg18` service hosts the whole suite).

## 3. 📄 Decision

Adopt a **single Postgres-only suite.**

- `phpunit.xml` keeps `DB_CONNECTION=pgsql`; CI's `pgvector/pgvector:pg18` service hosts every test (Unit, Feature, Architecture).
- `tests/Feature/Postgres` is retained as a marker directory for tests that explicitly assert Postgres-only features (`vector`, HNSW, `tsvector`, `GENERATED` columns, schema triggers). It is not a separate DB tier.
- The `composer test:pg` script remains as a convenience alias for that subset; `composer test:feature`/`test:unit`/`test:arch` all run on Postgres.
- CI runs the gate with the **Pest** runner (`vendor/bin/pest`), never raw `phpunit` (see ADR 0013 / the 2026-07-28 CI work).

## 4. 📄 Consequences

- One schema source of truth; CI cost is a single database, not two; no SQLite-only bug class is caught (acceptable — SQLite is not a deployment target).
- Every developer and CI runs against the production-shaped engine, so PG-specific behaviour (extensions, generated columns, triggers) is exercised on every run.
- All other ADR 0013 decisions (the four-layer Pest pyramid, 80 % coverage target as a ratchet, strict `level: max`, shrinking arch-rule carve-outs) stand.

## 5. 📄 References

- Supersedes the dual-suite clause of [ADR 0013 (Test Pyramid)](100326-test-pyramid.md) · [Wayfinder #17](https://github.com/s-a-c/samples-20260717/issues/17) · ADR 0018 (Acceptance Gates — Two-Environment Operational Gate) · [Wayfinder #40–#42](https://github.com/s-a-c/samples-20260717/issues/40) (Postgres pivot)
