---
title: "Domain Docs"
description: "This repo is **single-context**: one [`../../CONTEXT.md`](../../CONTEXT.md) at the"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: guide
tags: [guide, agents, domain, docs]
created: 2026-07-19
updated: 2026-08-17
---

# Domain Docs

This repo is **single-context**: one [`../../CONTEXT.md`](../../CONTEXT.md) at the
repo root, plus this `docs/agents/` directory.

## 1. Before exploring, read

- [`../../CONTEXT.md`](../../CONTEXT.md) — the domain glossary. Core terms:
    - **Sample Product** — one reference dataset + UX (Chinook, Northwind, Pagila).
      _Avoid_: tenant, customer account.
    - **Product Domain** — the bounded concepts of one Sample Product. _Avoid_:
      shared business domain, unified catalogue.
    - **Product Import** / **Product Reset** — the materialisation + validation
      pipeline per product.
- [`../../AGENTS.md`](../../AGENTS.md) — project scope + agent skills config.

If a referenced file does not exist, proceed silently. Do not flag absence or
suggest creating it upfront.

## 2. Use the glossary vocabulary

When writing docs, code, or commit messages, prefer the established terms from
`CONTEXT.md`. The three sample products (Chinook, Northwind, Pagila) are
independent — do not collapse them into a shared business domain.

## 3. Resolution order

1. This file + `CONTEXT.md` (repo-local).
2. Global default at `~/.config/agents/docs/agents/domain.md`.
