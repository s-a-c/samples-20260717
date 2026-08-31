---
title: "Acceptance Tests"
description: "This document describes the high-level acceptance tests and scenarios used to verify the Samples application meets its business requirements and user needs."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: reference
tags: [testing, acceptance, tests]
created: 2026-07-30
updated: 2026-08-17
---
# Acceptance Tests

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Purpose](#1--purpose)
- [2. 📄 Acceptance Matrix (TC-1)](#2--acceptance-matrix-tc-1)
  - [2.1. 📄 Scenario: Product Curator Access](#21--scenario-product-curator-access)
- [3. 📄 Core Acceptance Journeys](#3--core-acceptance-journeys)
  - [3.1. 📄 J-1: Search to Resource](#31--j-1-search-to-resource)
  - [3.2. 📄 J-2: Product Reset Lifecycle](#32--j-2-product-reset-lifecycle)
- [4. 📄 Operational Gates](#4--operational-gates)
- [5. 📄 Diagrams](#5--diagrams)

</details>

---
## 1. 📄 Purpose

This document describes the high-level acceptance tests and scenarios used to verify the Samples application meets its business requirements and user needs.

## 2. 📄 Acceptance Matrix (TC-1)

The primary acceptance mechanism is the **Authorization Acceptance Matrix**, which verifies that users with specific roles can or cannot access specific product resources.

### 2.1. 📄 Scenario: Product Curator Access

**Given** a user with the role `chinook_curator`,
**When** they attempt to access the Chinook Artists index,
**Then** they receive a `200 OK` response.

**When** they attempt to access the Pagila Films index,
**Then** they receive a `403 Forbidden` response.

## 3. 📄 Core Acceptance Journeys

### 3.1. 📄 J-1: Search to Resource

1. User enters "Black Sabbath" in the federated search.
2. Result "Black Sabbath (Artist - Chinook)" appears with high relevance.
3. Clicking the link navigates the user to `/filament/chinook/artists/{uuid}`.
4. The user verifies the artist's albums are visible.

### 3.2. 📄 J-2: Product Reset Lifecycle

1. Operator requests a reset token for the Northwind domain.
2. System provides a signed token.
3. Operator confirms the reset via the CLI.
4. System truncates the `northwind` schema and reloads from source shadow schemas.
5. Operator verifies the Northwind dashboard shows fresh record counts.

## 4. 📄 Operational Gates

Following **ADR 0034**, the project uses the "Operational Gate" pattern:

- **Pre-flight:** `php artisan pgsql:check` ensures extensions and schemas are ready.
- **Post-flight:** `php artisan dossier:generate` ensures documentation and evidence are in sync.

## 5. 📄 Diagrams

[Acceptance Test Flow](../assets/acceptance-test-flow.mmd)

````mermaid
---
config:
  theme: redux-dark-color
  themeVariables:
    background: "#1e1e2e"
    primaryColor: "#89b4fa"
    primaryTextColor: "#1e1e2e"
    primaryBorderColor: "#74c7ec"
    lineColor: "#74c7ec"
    textColor: "#cdd6f4"
    fontSize: 16px
---
sequenceDiagram
    participant C as CI Runner
    participant T as Pest Acceptance Test
    participant A as App / Fortify
    participant D as DB (Postgres)

    C->>T: Run Acceptance Matrix
    T->>A: POST /login (Credentials)
    A->>D: Verify User & Team
    D-->>A: User Record
    A-->>T: 302 Redirect / Dashboard
    T->>A: GET /filament/chinook/artists
    A->>D: Authorized? (Shield/Policy)
    D-->>A: Yes
    A-->>T: 200 OK (Filament Table)
    T->>T: assertSee("Artists")```
````
