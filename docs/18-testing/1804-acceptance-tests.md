---
title: "Acceptance Tests"
description: "This document describes the high-level acceptance tests and scenarios used to verify the Samples application meets its business requirements and user needs."
type: testing
tags: \[testing, acceptance, tests]
updated: 2026-07-30
---

# Acceptance Tests

## Purpose

This document describes the high-level acceptance tests and scenarios used to verify the Samples application meets its business requirements and user needs.

## Acceptance Matrix (TC-1)

The primary acceptance mechanism is the **Authorization Acceptance Matrix**, which verifies that users with specific roles can or cannot access specific product resources.

### Scenario: Product Curator Access

**Given** a user with the role `chinook_curator`,
**When** they attempt to access the Chinook Artists index,
**Then** they receive a `200 OK` response.

**When** they attempt to access the Pagila Films index,
**Then** they receive a `403 Forbidden` response.

## Core Acceptance Journeys

### J-1: Search to Resource

1. User enters "Black Sabbath" in the federated search.
2. Result "Black Sabbath (Artist - Chinook)" appears with high relevance.
3. Clicking the link navigates the user to `/filament/chinook/artists/{uuid}`.
4. The user verifies the artist's albums are visible.

### J-2: Product Reset Lifecycle

1. Operator requests a reset token for the Northwind domain.
2. System provides a signed token.
3. Operator confirms the reset via the CLI.
4. System truncates the `northwind` schema and reloads from source shadow schemas.
5. Operator verifies the Northwind dashboard shows fresh record counts.

## Operational Gates

Following **ADR 0034**, the project uses the "Operational Gate" pattern:

- **Pre-flight:** `php artisan pgsql:check` ensures extensions and schemas are ready.
- **Post-flight:** `php artisan dossier:generate` ensures documentation and evidence are in sync.

## Diagrams

[Acceptance Test Flow](../../assets/acceptance-test-flow.mmd)

````mermaid
---
title: Diagram
config:
  theme: redux-dark-color
  themeVariables:
    background: "#1e1e2e"
    primaryColor: "#89b4fa"
    primaryTextColor: "#1e1e2e"
    primaryBorderColor: "#74c7ec"
    lineColor: "#74c7ec"
    secondaryColor: "#cba6f7"
    secondaryTextColor: "#1e1e2e"
    secondaryBorderColor: "#b4befe"
    tertiaryColor: "#94e2d5"
    tertiaryTextColor: "#1e1e2e"
    tertiaryBorderColor: "#89dceb"
    mainBkg: "#89b4fa"
    secondBkg: "#cba6f7"
    tertiaryBkg: "#94e2d5"
    textColor: "#cdd6f4"
    nodeBorder: "#74c7ec"
    clusterBkg: "#313244"
    clusterBorder: "#b4befe"
    edgeLabelBackground: "#1e1e2e"
    titleColor: "#cdd6f4"
    fontSize: 16px
  themeCSS: |
    .node rect, .node circle, .node ellipse, .node polygon, .node path { stroke-width: 2px !important; }
    .edgePath .path { stroke-width: 2px !important; }
    .label { font-weight: bold !important; }
    .edgeLabel { background-color: #1e1e2e !important; }
  sequence:
    actorBkg: "#313244"
    actorBorder: "#74c7ec"
    actorTextColor: "#cdd6f4"
    actorLineColor: "#74c7ec"
    signalColor: "#74c7ec"
    signalTextColor: "#cdd6f4"
    labelBoxBkgColor: "#45475a"
    labelBoxBorderColor: "#b4befe"
    labelTextColor: "#cdd6f4"
    loopTextColor: "#cdd6f4"
    noteBkgColor: "#fab387"
    noteBorderColor: "#f9e2af"
    noteTextColor: "#1e1e2e"
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
