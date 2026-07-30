---
title: "ADR 0018: Acceptance & Operational Gates"
description: "Accepted — restated from [Wayfinder #11](https://github.com/s-a-c/samples-20260717/issues/11) (map [#15](https://github.com/s-a-c/samples-20260717/issues/15))."
type: adr
tags: \[adr, "0018", acceptance]
updated: 2026-07-30
---

# ADR 0018: Acceptance & Operational Gates

## Status

Accepted — restated from [Wayfinder #11](https://github.com/s-a-c/samples-20260717/issues/11) (map [#15](https://github.com/s-a-c/samples-20260717/issues/15)).

## Context

The application needs a strict, traceable acceptance model: "mostly working" is not acceptable. Every named gate is mandatory and must retain repeatable evidence. This decision fixes what "done" means across the whole delivery and how it is proven on two environments.

## Decision

Adopt a risk-ordered, evidence-backed acceptance model.

**Risk-ordered delivery stages** (a stage may not advance until its gates pass):

1. Scaffold/toolchain, panel roots, and test harness.
2. Postgres extension capability on Herd and CI (`vector`, `unaccent`, `pg_trgm`, `en_unaccent`).
3. Core identity/teams, schema, UUIDv7/source identity, and policies.
4. Source acquisition, validated import, and independent reset.
5. Sample-panel resource abilities and authorization.
6. Hybrid (lexical + semantic) and federated search, and recovery.
7. Admin portfolio, team dashboards, and approved package boundaries.
8. Final acceptance, documentation, CI, and Herd evidence.

**Mandatory acceptance evidence** (the four families):

- **Baseline Evidence Fixture** — per-product manifest/artifact/digest, source and target counts, identity mappings, normalisation/relationship/index outcomes. Passes on first import and on reset after permitted edits/removals.
- **Reset Isolation Proof** — success and fault injection; restore only the target baseline while preserving Domain Identity; leave other domains, core app, and Team Artefacts untouched; retain no partial publish; expose only the target Reset Window; retain the Reset Run/evidence until search gates complete or recovery resolves.
- **Authorization Acceptance Matrix** — every role × action × panel boundary at policy and HTTP/Filament-action level; proves Team Artefact scope never becomes a query filter on shared sample data.
- **Golden Search Corpus** — reviewed product-labelled Tier 1, Tier 2, and federated queries with expected identities, filters, labels, and deep links. Deterministic lexical behaviour is exact; semantic/hybrid relevance is top-k based. Reruns after import, reset, projection failure, and recovery.

**Two-Environment Operational Gate:** full Linux x86_64 CI evidence on every pull request (`pgvector/pgvector:pg18` service), and Herd macOS arm64 CLI/HTTP evidence per release candidate. Either failure blocks acceptance.

**Implementation-Readiness Dossier:** a version-controlled record (ADR 0015) maps each approved decision to its gates, tests, operator commands, evidence location, and recovery procedure. Generated evidence stays as CI/release artifacts, not committed output.

## Consequences

- "Done" is gate-bound, not opinion-bound; no stage advances on partial evidence.
- CI must mirror the Herd production shape (Postgres + pgvector) — see ADR 0021.
- The four evidence families drive test design across the suite (Authorization Matrix, Reset Isolation, Golden Corpus, Baseline Fixture).

## References

- [Wayfinder #11](https://github.com/s-a-c/samples-20260717/issues/11) · ADR 0015 (Dossier) · ADR 0013 (Test Pyramid) · ADR 0021 (Single Postgres Test Suite)
