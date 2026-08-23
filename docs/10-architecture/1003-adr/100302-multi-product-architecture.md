---
title: "ADR 0001: Multi-Product Architecture"
description: "Decision to keep Chinook, Northwind, and Pagila as distinct sample products with shared infrastructure"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, architecture, multi-product]
created: 2026-07-25
updated: 2026-08-17
---

# ADR 0001: Multi-Product Architecture

**Status:** Accepted
**Date:** 2026-07-25
**Context:** Three unrelated reference datasets — Chinook (music media store), Northwind (supply chain / orders), and Pagila (DVD rental) — need to be presented as distinct sample products within the same Laravel application. Each dataset has its own business concepts, relationships, and semantics. They cannot be merged into a unified domain model without losing their pedagogical value as independent reference datasets. The application must also provide shared cross-product capabilities: user authentication, team management, search, and an administrative overview.

**Decision:** Separate Filament panels per product domain, backed by dedicated database schemas, with shared infrastructure (auth, teams, search) housed in the `public` schema.

**Consequences:**

- Each product maintains its own domain model
- Shared infrastructure avoids duplication
- Team-based access control bridges domains

**References:**

- ADR 0005: Filament Panel Isolation
