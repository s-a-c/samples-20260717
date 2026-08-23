---
title: "Traceability Matrix"
description: "Traceability mapping from problem and goals through requirements, acceptance criteria, and tests."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [requirements, traceability, matrix]
created: 2026-07-30
updated: 2026-08-17
---

# Traceability Matrix

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Traceability Chain](#2-traceability-chain)
- [3. Gap Analysis](#3-gap-analysis)
- [4. Diagrams](#4-diagrams)

</details>

---

## 1. Purpose

This document maps project requirements to their corresponding goals, user stories, architectural decisions, and verification tests.

## 2. Traceability Chain

| ID       | Goal   | Functional Requirement     | User Story | ADR      | Test Case                | Status |
| :------- | :----- | :------------------------- | :--------- | :------- | :----------------------- | :----- |
| **TR-1** | GOAL-1 | FR-1.2 (Dedicated Schemas) | US-1       | ADR 0001 | `PostgresExtensionsTest` | ✅     |
| **TR-2** | GOAL-1 | FR-1.1 (Multi-product)     | US-1       | ADR 0005 | `ChinookImporterTest`    | ✅     |
| **TR-3** | GOAL-2 | FR-2.1 (Fortify Auth)      | US-1       | ADR 0008 | `AuthenticationTest`     | ✅     |
| **TR-4** | GOAL-3 | FR-4.1 (Federated Search)  | US-1       | ADR 0003 | `FederatedSearchTest`    | ✅     |
| **TR-5** | GOAL-3 | FR-4.3 (Vector Search)     | US-1       | ADR 0009 | `EmbeddingJobTest`       | ✅     |
| **TR-6** | GOAL-4 | FR-3.2 (Reset Workflow)    | US-2       | ADR 0007 | `ResetWindowTest`        | ✅     |
| **TR-7** | GOAL-1 | NFR-1.1 (UUIDv7)           | US-1       | ADR 0002 | `PostgresExtensionsTest` | ✅     |

## 3. Gap Analysis

- **Gap-1:** Traceability from AC-1 to specific unit tests for all 25 resources is partially complete (manual verification required).
- **Gap-2:** Non-functional requirements for performance (NFR-2.1) lack automated benchmark tests in CI.

## 4. Diagrams

[Requirements Traceability](../assets/requirements-traceability.mmd)

````mermaid
---
title: MVP Scope
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
---
flowchart LR
    subgraph InScope["MVP: In-Scope"]
        direction TB
        P1["Chinook Media Management"]
        P2["Northwind Supply Chain"]
        P3["Pagila Video Rental"]
        Infra["PostgreSQL 18 + UUIDv7"]
        Auth["Fortify Auth + RBAC"]
        Search["Federated Search + RRF"]
        Admin["Admin Portfolio View"]

        P1 ~~~ P2 ~~~ P3 ~~~ Infra ~~~ Auth ~~~ Search ~~~ Admin
    end

    subgraph OutOfScope["Future: Out-of-Scope"]
        direction TB
        E1["Cross-product Data Sync"]
        E2["Multi-tenancy (Filament native)"]
        E3["Direct Data Editing (Source Schemas)"]

        E1 ~~~ E2 ~~~ E3
    end

    InScope -.-> OutOfScope```
````
