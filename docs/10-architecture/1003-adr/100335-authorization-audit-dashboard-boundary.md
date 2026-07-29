# ADR 0019: Authorization, Audit & Dashboard Boundary

## Status

Accepted — restated from [Wayfinder #13](https://github.com/s-a-c/samples-20260717/issues/13) (map [#15](https://github.com/s-a-c/samples-20260717/issues/15)). Refined by ADR 0008 (Spatie + Shield + Fortify Coexistence).

## Context

The application spans three sample products plus team and admin surfaces. It needs an explicit package boundary for authorization, audit, and configurable dashboards — one that keeps team scope from ever leaking into shared sample data, and that does not let a generic dynamic-form package own dashboard definitions.

## Decision

**Authorization**

- **Spatie Laravel Permission** is the sole global-capability substrate. Roles group permissions and are assigned to users; normal administration does not grant direct permissions to individual users.
- Model **Sample Curator** and **System Operator** as global capability roles. **Team Owner** remains a Team Membership responsibility; universal **Product Entitlement** is implicit for every authenticated user.
- **Filament Shield** is used **only in the Admin Panel** for global-role administration and reviewed policy/permission scaffolding, with a panel-qualified permission allow-list. Laravel policies remain the decisive enforcement boundary. Shield tenancy is not enabled; generated output does not redefine team scope or source-domain rules.

**Audit and presentation**

- **Spatie Activitylog** is used only for selected security and operator events — no Blob Asset payloads, and no automatic claim to be the operational record.
- **Reset Evidence** (ADR 0007) remains the dedicated source of truth for Product Reset outcome and recovery.
- **Lara Zeus Activity Timeline** is deferred until a concrete need to render selected activity arises; it is a presentation adapter, not an audit/authorization dependency.

**Dashboard boundary and exclusions**

- Portfolio Cards and Team Dashboard configuration are code-owned and allow-listed in Filament/Livewire; no generic dynamic-form package owns their definitions.
- Guardian, Flex Fields, and Shield Enhanced are not selected. Shield Enhanced is reassessed only if a concrete non-CRUD permission need appears.
- Human-readable/shareable Team Artefact URLs and slug policy are deferred (ADR 0012); no sluggable package is selected here.

## Consequences

- A single RBAC engine (Spatie); team scope is membership data, never a query filter on sample data.
- Audit is layered: Activitylog for selected events, Reset Evidence for resets — no single package claims to be the operational record.
- Dashboards stay code-owned, keeping the configuration surface auditable.

## References

- [Wayfinder #13](https://github.com/s-a-c/samples-20260717/issues/13) · ADR 0008 (Spatie + Shield + Fortify) · ADR 0007 (Product Reset) · ADR 0012 (Team Artefacts) · ADR 0011 (Portfolio Card)
