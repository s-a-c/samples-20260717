---
title: "1003-adr — Contents"
description: "Structural contents page for the Architecture Decision Records: every ADR in prefix order."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: contents
tags: [contents, navigation, adr]
created: 2026-07-25
updated: 2026-08-23
---

# 1003-adr — Contents

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Direct children](#2-direct-children)

</details>

---

Architecture Decision Records: each captures one decision with its status, context, decision, and consequences. Read in numeric order; the semantic index is [`100398-index.md`](./100398-index.md). Parent: [`1001-contents.md`](../1001-contents.md).

## 1. Purpose

Architecture Decision Records for the samples application. Each ADR is a numbered, self-contained record; newer ADRs may supersede older ones. Status is tracked per ADR as Proposed, Accepted, Rejected, or Superseded.

## 2. Direct children

| Entry                                                                                                     | Kind     | Notes                                                                                                                                                                             |
| --------------------------------------------------------------------------------------------------------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [ADR 0001: Multi-Product Architecture](./100302-multi-product-architecture.md)                            | Document | Decision to keep Chinook, Northwind, and Pagila as distinct sample products with shared infrastructure                                                                            |
| [ADR 0002: UUIDv7 for All Entities](./100304-uuidv7-for-all-entities.md)                                  | Document | Decision to use UUIDv7 identifiers for shared and product-domain entities.                                                                                                        |
| [ADR 0003: Postgres-Native Search (Hybrid)](./100307-postgres-native-search.md)                           | Document | Documentation for ADR 0003: Postgres-Native Search (Hybrid).                                                                                                                      |
| [ADR 0004: Shadow-Schema Import Pipeline](./100308-shadow-schema-import-pipeline.md)                      | Document | Documentation for ADR 0004: Shadow-Schema Import Pipeline.                                                                                                                        |
| [ADR 0005: Filament Panel Isolation](./100311-filament-panel-isolation.md)                                | Document | Decision to isolate Chinook, Northwind, and Pagila behind separate Filament panels.                                                                                               |
| [Source Identity Registry](./100313-source-identity-registry.md)                                          | Document | Standardized way to identify source systems for sample products                                                                                                                   |
| [0007 - Product Reset Semantics](./100314-product-reset-semantics.md)                                     | Document | Understanding how reset operations affect different product models.                                                                                                               |
| [0008 - Spatie + Shield + Fortify Coexistence](./100316-spatie-shield-fortify-coexistence.md)             | Document | Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer.                                                                                                   |
| [0009 - Search Document Shape + Federation](./100317-search-document-shape.md)                            | Document | Documentation requires consistent schema for search operations across all products.                                                                                               |
| [0010 - Embedding Profile + AI SDK](./100319-embedding-profile.md)                                        | Document | The application integrates with AI providers for embedding generation.                                                                                                            |
| [0011 - Portfolio Card Architecture](./100322-portfolio-card-architecture.md)                             | Document | Portfolio cards represent sample product records across domains.                                                                                                                  |
| [0012 - Team Artefacts Schema](./100323-team-artefacts-schema.md)                                         | Document | Team artefacts (documents, diagrams) need structured storage.                                                                                                                     |
| [0013 - Test Pyramid](./100326-test-pyramid.md)                                                           | Document | Testing strategy for multiple domains needs standardization.                                                                                                                      |
| [0014 - Larastan Target Level + Baseline Policy](./100328-larastan-baseline.md)                           | Document | Larastan provides static analysis for the application but requires configuration.                                                                                                 |
| [0015 - Implementation-Readiness Dossier](./100329-implementation-readiness-dossier.md)                   | Document | The application's readiness for production deployment requires structured validation.                                                                                             |
| [0016 - Documentation Lifecycle](./100331-documentation-lifecycle.md)                                     | Document | Technical documentation requires standardized maintenance and review processes.                                                                                                   |
| [0017 - Git Branch + PR + Dependency Strategy](./100332-git-branch.md)                                    | Document | The application needs a standardized Git workflow to manage feature branches, PRs, and dependencies.                                                                              |
| [ADR 0018: Acceptance & Operational Gates](./100334-acceptance-and-operational-gates.md)                  | Document | Accepted — restated from [Wayfinder #11](https://github.com/s-a-c/samples-20260717/issues/11) (map [#15](https://github.com/s-a-c/samples-20260717/issues/15)).                   |
| [ADR 0021: Single Postgres Test Suite](./100337-single-postgres-test-suite.md)                            | Document | Accepted — amends the dual-suite clause of [ADR 0013 (Test Pyramid)](./100326-test-pyramid.md) / [Wayfinder #17](https://github.com/s-a-c/samples-20260717/issues/17).            |
| [ADR 0033: SamplesProduct Enum](./100338-samples-product-enum.md)                                         | Document | Centralized Sample Product identity in a string-backed, Filament-enhanced enum                                                                                                    |
| [ADR 0019: Authorization, Audit & Dashboard Boundary](./100341-authorization-audit-dashboard-boundary.md) | Document | Documentation page for 100341-authorization-audit-dashboard-boundary.                                                                                                             |
| [ADR 0020: Filament Resource Generation](./100343-filament-resource-generation.md)                        | Document | Documentation page for 100343-filament-resource-generation.                                                                                                                       |
| [ADR 0023: Unlogged Cache Tables + Local PgBouncer](./100344-unlogged-cache-pgbouncer.md)                 | Document | Accepted — Postgres-only hardening: UNLOGGED cache tables, transaction-pooled local connections, from [Wayfinder map #114](https://github.com/s-a-c/samples-20260717/issues/114). |
| [1003-adr Documentation Index](./100398-index.md)                                                         | Document | Index and glossary for the 1003-adr documentation tree                                                                                                                            |
