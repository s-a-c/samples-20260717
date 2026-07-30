---
title: "API Specification"
description: "This document defines the internal API and service contracts used by the Samples application, focusing on search, import, and reset interactions."
type: design
tags: \[design, api, specification]
updated: 2026-07-30
---

# API Specification

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Search Service Contract](#2-search-service-contract)
    - [2.1. `search(string $query): Collection`](#21-searchstring-query-collection)
- [3. Import Pipeline Contract](#3-import-pipeline-contract)
    - [3.1. `import(string $product): void`](#31-importstring-product-void)
- [4. Product Reset Contract](#4-product-reset-contract)
    - [4.1. `open(string $product): string`](#41-openstring-product-string)
    - [4.2. `confirm(string $token): void`](#42-confirmstring-token-void)
- [5. Diagrams](#5-diagrams)

</details>

---

## 1. Purpose

This document defines the internal API and service contracts used by the Samples application, focusing on search, import, and reset interactions.

## 2. Search Service Contract

The `FederatedSearchService` provides the primary interface for cross-domain discovery.

### 2.1. `search(string $query): Collection`

- **Input:** Natural language search string.
- **Process:**
    1. Generates a temporary embedding for the query.
    2. Executes parallel queries against `search_projections` in all product schemas.
    3. Combines TSV (BM25-like) and Vector (Cosine similarity) ranks.
    4. Applies `ReciprocalRankFusion` to interleave results.
- **Output:** A collection of `SearchResult` objects containing the entity type, UUID, and relevance score.

## 3. Import Pipeline Contract

The `ProductImportPipeline` manages the ingestion of reference data.

### 3.1. `import(string $product): void`

- **Input:** Product name (chinook, northwind, or pagila).
- **Process:**
    1. Identifies the source files/schemas.
    2. Truncates the target product schema.
    3. Maps source records to UUIDv7 entities.
    4. Populates the `source_identities` registry.
    5. Dispatches `EmbeddingJob` for all new records.

## 4. Product Reset Contract

The `ResetWindow` and `ResetConfirmationService` manage state-safe resets.

### 4.1. `open(string $product): string`

- **Process:** Generates a signed reset token and creates a `reset_runs` entry with status `pending`.
- **Output:** The signed confirmation token.

### 4.2. `confirm(string $token): void`

- **Process:** Validates the token and transitions the `reset_runs` status to `running`, triggering the `ProductImportPipeline`.

## 5. Diagrams

[API Interaction Sequence](../assets/api-interaction-sequence.mmd)

```mermaid
---
title: API Interaction Sequence
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
    participant U as User
    participant F as Filament Panel
    participant S as FederatedSearchService
    participant R as ReciprocalRankFusion
    participant DB as PostgreSQL (pgvector)

    U->>F: Enter Search Term
    F->>S: search(query)
    par Parallel Queries
        S->>DB: Search Chinook (TSV + Vector)
        S->>DB: Search Northwind (TSV + Vector)
        S->>DB: Search Pagila (TSV + Vector)
    end
    DB-->>S: Return ranked results
    S->>R: fusion(results)
    R-->>S: Interleaved & Re-ranked list
    S-->>F: Search results
    F-->>U: Display results with deep links
```
