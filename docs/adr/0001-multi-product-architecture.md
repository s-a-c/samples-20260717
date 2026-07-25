# ADR 0001: Multi-Product Architecture

**Status:** Accepted
**Date:** 2026-07-25
**Context:** Three unrelated reference datasets — Chinook (music media store), Northwind (supply chain / orders), and Sakila (DVD rental) — need to be presented as distinct sample products within the same Laravel application. Each dataset has its own business concepts, relationships, and semantics. They cannot be merged into a unified domain model without losing their pedagogical value as independent reference datasets. The application must also provide shared cross-product capabilities: user authentication, team management, search, and an administrative overview.

**Decision:** Separate Filament panels per product domain, backed by dedicated database schemas, with shared infrastructure (auth, teams, search) housed in the `public` schema.

- Each product domain gets its own Filament panel with scoped resource discovery and independent navigation.
- An Admin Panel provides cross-product operations and administration.
- Spatie Laravel Permission roles (`chinook_curator`, `northwind_curator`, `sakila_curator`, `super_admin`) gate access to each panel via Filament's `canAccessPanel`.
- The `public` schema holds shared tables — users, teams, roles, permissions, search documents, source identity registries — that are not owned by any single product domain.
- Each product domain's tables reside in its own PostgreSQL schema (`chinook`, `northwind`, `sakila`) to provide physical and logical isolation.

**Consequences:**
- **Positive:** Clear separation of concerns; each product can be developed, tested, and reset independently.
- **Positive:** Shared infrastructure avoids duplication of auth, teams, and search logic.
- **Positive:** Schema-level isolation prevents accidental cross-domain JOINs or name collisions.
- **Tradeoff:** More complex database migration management (multi-schema).
- **Tradeoff:** Cross-product features (federated search, admin portfolio) require explicit orchestration.
- **Tradeoff:** Authentication and authorization must be shared but role-scoped — RBAC is the binding mechanism.

**Related:**
- [ADR 0005: Filament Panel Isolation](0005-filament-panel-isolation.md)
- [ADR 0004: Shadow-Schema Import Pipeline](0004-shadow-schema-import-pipeline.md)
- [CONTEXT.md](../../CONTEXT.md) — domain glossary (Sample Product, Product Domain, Sample Panel, Admin Panel)
