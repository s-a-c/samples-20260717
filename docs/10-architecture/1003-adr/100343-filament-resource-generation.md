---
title: "ADR 0020: Filament Resource Generation"
description: "Documentation page for 100343-filament-resource-generation."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [documentation]
created: 2026-08-17
updated: 2026-08-17
---

# ADR 0020: Filament Resource Generation

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Status](#1-status)
- [2. Context](#2-context)
- [3. Decision](#3-decision)
- [4. Consequences](#4-consequences)
- [5. References](#5-references)

</details>

---

## 1. Status

Accepted — restated from [Wayfinder #30](https://github.com/s-a-c/samples-20260717/issues/30) (map [#15](https://github.com/s-a-c/samples-20260717/issues/15)). Realised namespace updated to the project's Laravel-native convention (see Consequences).

## 2. Context

~30–45 Filament resources span three product panels plus an Admin panel. The decision fixes how they are created, where they live, and how Shield permissions are bound — so that adding a product or resource is mechanical and panel isolation is auditable at the permission level.

## 3. Decision

**1. Generation approach — generator-first, then tailor.** Use `make:filament-resource` per entity with the `--panel` flag to scaffold directly into the correct panel namespace, then hand-edit for product-specific form/table/relation-manager tailoring. Rejected: hand-rolled-from-start (repetitive boilerplate) and hybrid clone-and-adapt (inconsistent skeletons).

**2. Invocation pattern:**

```bash
php artisan make:filament-resource {Entity} \
  --panel={chinook|northwind|pagila} \
  --model-namespace="App\\Models\\{Product}" \
  --generate
```

`--generate` auto-infers form schema and table columns from the database schema; `--panel` routes output to `app/Filament/{Panel}/Resources/`. Admin resources (`RoleResource`, etc.) use `--panel=admin` and are limited to Shield role/permission administration per ADR 0019.

**3. Panel ownership — implicit via namespace.** Resources declare their owning panel through filesystem location/namespace (`App\Filament\{Panel}\Resources\…`). No explicit `getPanel()` method is required; Filament resolves association from the registered class.

**4. Shield policy binding — panel-qualified permissions.** Run `shield:generate` per panel. Permissions are named `{panel}_{entity}.{action}` (e.g. `chinook_album.view_any`). Roles compose from these: `{product}_curator` gets all `{product}_*` permissions; `super_admin` gets everything via `Gate::before`. Panel-qualified (not generic) permissions are chosen so `view_any` controls nav visibility per panel, isolation is auditable at the permission level, and adding a product is one `shield:generate` + one new curator role with zero changes to existing resources.

## 4. Consequences

- The decision's original text referenced `App\\Domain\\{Product}\\Models` and the `sakila` product. The realised project uses the **Laravel-native `App\Models\{Product}` namespace** (enforced by architecture rules) and the product is named **Pagila**. The generation intent is unchanged; only the namespace/product label differ.
- ~180–270 panel-qualified permissions across three products — manageable, and auditable.
- Adding a Sample Product is mechanical: one panel provider, `make:filament-resource` per entity, one `shield:generate`, one `{product}_curator` role.

## 5. References

- [Wayfinder #30](https://github.com/s-a-c/samples-20260717/issues/30) · ADR 0005 (Filament Panel Isolation) · ADR 0008 (Spatie + Shield + Fortify) · ADR 0019 (Authorization Boundary)
