---
title: "ADR 0005: Filament Panel Isolation"
description: "Decision to isolate Chinook, Northwind, and Pagila behind separate Filament panels."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0005", filament]
created: 2026-07-25
updated: 2026-08-17
---
# ADR 0005: Filament Panel Isolation

**Status:** Accepted
**Date:** 2026-07-25
**Context:** The application exposes four administrative areas: three Sample Panels (one per product domain — Chinook, Northwind, Pagila) and one cross-product Admin Panel. Each panel must present only its own resources, navigation items, and dashboards. A curator of Chinook data must never see Northwind or Pagila resources in their sidebar, and vice versa. Filament's default single-panel configuration causes all registered resources to appear in the same navigation. Filament supports multi-panel configuration (since v3.x) via multiple panel providers, each with independent resource discovery, middleware stacks, and paths.

**Decision:** Configure four separate Filament panel providers, each with scoped resource discovery and role-based `canAccessPanel` gating.

- **Panel providers:**
    - `App\Providers\Filament\ChinookPanelProvider` — Chinook resources, path `/chinook`
    - `App\Providers\Filament\NorthwindPanelProvider` — Northwind resources, path `/northwind`
    - `App\Providers\Filament\PagilaPanelProvider` — Pagila resources, path `/pagila`
    - `App\Providers\Filament\AdminPanelProvider` — cross-product operations, path `/admin`
- **Scoped discovery:** Each panel provider's `discoverResources()` points to its own resource directory (e.g., `app/Filament/Chinook/Resources/`).
- **Authentication:** All panels share the same authentication guard (Fortify), but each panel has its own `auth()` configuration for middleware.
- **Access gating:** Each panel's `canAccessPanel()` uses Spatie roles:
    - Chinook panel → `chinook_curator` or `super_admin`
    - Northwind panel → `northwind_curator` or `super_admin`
    - Pagila panel → `pagila_curator` or `super_admin`
    - Admin panel → `super_admin` only
- **Navigation:** Each panel's `navigation()` is scoped to its own resource set. No shared navigation items.
- **Fortify coexistence:** Filament's panel auth is layered on top of Fortify — Fortify handles credential and session management; Filament panels handle panel-level authorization.

**Consequences:**

- **Positive:** No cross-domain leakage — each curator role sees exactly one Sample Panel. Navigation is per-panel and never mixes resources.
- **Positive:** Independent resource registration — adding a resource to Chinook panel cannot affect Pagila panel.
- **Positive:** Panel-level middleware can be customized independently (rate limits, IP restrictions, audit logging).
- **Positive:** Each panel can have its own theme, branding, and dashboard widgets without conflicts.
- **Tradeoff:** Duplicate configuration — each panel provider must configure its own resources, widgets, and pages, leading to some repetition.
- **Tradeoff:** Four panel provider boot classes instead of one — minimal but measurable overhead.
- **Tradeoff:** A user assigned multiple curator roles must visit separate URLs for each panel; there is no unified "all resources" view.
- **Tradeoff:** Super admin who needs access to all panels must navigate between them explicitly — the Admin Panel serves as the hub.

**Related:**

- [ADR 0001: Multi-Product Architecture](100302-multi-product-architecture.md) — overall multi-product architecture
- [CONTEXT.md](../../../CONTEXT.md) — Sample Panel, Admin Panel, Sample Curator, System Operator
