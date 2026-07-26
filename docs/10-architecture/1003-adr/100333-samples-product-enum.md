---
title: SamplesProduct Enum
description: Centralise Sample Product identity in a string-backed, Filament-enhanced enum
type: architecture
tags: [architecture, design, documentation]
updated: 2026-07-26
category: documentation
---

# ADR 0033: SamplesProduct Enum

**Status:** Accepted
**Date:** 2026-07-26

**Context:** Sample Product identity was scattered across the codebase as raw string literals — `'chinook'`, `'northwind'`, `'pagila'` appeared in ~60 locations: the `HasProductDomain` contract (returning `string`), ~30 domain models, four Filament panel providers, `User::canAccessPanel`, the Portfolio widgets, and the `BelongsToProductDomain` write-gating trait. There was no single source of truth for per-product metadata (label, colour, icon, curator role, panel path), so each consumer hard-coded its own copy. Adding a product — and the project plans ten or more over time — required finding and editing every site, with no compile-time guarantee of completeness.

**Decision:** Introduce `App\Enums\SamplesProduct`, a `string`-backed enum implementing Filament's `HasLabel`, `HasColor`, and `HasIcon` contracts. Each case's full identity (label, colour name, full Tailwind palette, Heroicon, curator role, path, description) is co-located in a single `profile()` match arm returning a readonly `ProductProfile` value object, so adding a product is exactly one new arm. Membership is derived via `tryFrom()` / `fromPanelId()`, eliminating parallel identifier lists. Migrate `HasProductDomain::getProductDomainName(): string` to `getProductDomain(): SamplesProduct`, and propagate the enum type through the `BelongsToProductDomain` trait and `ResetWindow::assertWritable` so the type boundary holds end-to-end. The three Sample Panel providers derive their `id`, `path`, and primary colour from the enum; the Admin Panel is intentionally excluded (it is the Core Application, not a Sample Product). Volatile presentation data (Portfolio stat counts) stays out of the enum.

**Consequences:**

- **Positive:** One source of truth for Sample Product identity; per-product metadata is co-located; adding a product is a single, mechanical, exhaustive-checked edit.
- **Positive:** Compile-time exhaustiveness — a new case with no `profile()` arm is a fatal error, not a silent gap.
- **Positive:** Filament forms/tables consume the enum natively (label/colour/icon) without per-field mapping.
- **Tradeoff:** The contract change is a ~37-file migration; reverting is costly. This is accepted because the alternative (stringly-typed identity) does not scale to the planned product set.
- **Tradeoff:** The enum is a closed registry — adding a product also requires a new Panel Provider, resource directories, models, an import pipeline, a CONTEXT.md entry, and its own ADR-level work. The enum removes identity edits from that list but not the rest.

**Related:**

- [ADR 0001: Multi-Product Architecture](100302-multi-product-architecture.md)
- [ADR 0005: Filament Panel Isolation](100311-filament-panel-isolation.md)
- [CONTEXT.md](../../CONTEXT.md) — Sample Product, Sample Panel, Sample Curator
