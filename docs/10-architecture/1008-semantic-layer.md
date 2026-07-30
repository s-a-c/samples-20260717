---
title: "Semantic Layer"
description: "This document describes the semantic layer of the Samples application, detailing how physical data is operationalized, mapped, and consumed as canonical business concepts."
type: architecture
tags: \[architecture, semantic, layer]
updated: 2026-07-30
---

# Semantic Layer

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Semantic Mappings](#2-semantic-mappings)
    - [2.1. Physical to Canonical Mapping](#21-physical-to-canonical-mapping)
    - [2.2. Search Projection Semantics](#22-search-projection-semantics)
- [3. Metrics \& KPIs](#3-metrics--kpis)
- [4. Access Control \& Governance](#4-access-control--governance)
- [5. Consumption](#5-consumption)
- [6. Diagrams](#6-diagrams)

</details>

---

## 1. Purpose

This document describes the semantic layer of the Samples application, detailing how physical data is operationalized, mapped, and consumed as canonical business concepts.

## 2. Semantic Mappings

The semantic layer bridges the gap between the physical PostgreSQL schemas and the business logic in Laravel.

### 2.1. Physical to Canonical Mapping

- **Physical:** `chinook.artists.ArtistId` (Integer)
- **Canonical:** `App\Models\Chinook\Artist->id` (UUIDv7)
- **Transformation:** During the import phase, the `ChinookImporter` generates a UUIDv7 and records the original ID in the `source_identities` registry.

### 2.2. Search Projection Semantics

Each domain model is projected into a searchable document format:

- **Dimensions:** Product Domain, Resource Type.
- **Measures:** Record Relevance (weighted TSV + Vector distance).
- **Transformation:** The `SearchProjection` model concatenates key attributes (name, description, tags) into a `document_tsv` and a high-dimensional `embedding`.

## 3. Metrics & KPIs

The semantic layer provides the following canonical metrics:

- **Product Health:** Record counts vs. expected baseline (via `PortfolioSnapshotStats`).
- **Search Quality:** Mean Reciprocal Rank (MRR) of search results (Planned).
- **Import Velocity:** Records ingested per second during pipeline execution.

## 4. Access Control & Governance

Access to the semantic layer is governed by:

- **Eloquent Policies:** Scoping queries to the current user's team and role.
- **Filament Resources:** Defining the visible "projection" of the domain models to the end-user.

## 5. Consumption

- **APIs:** The `FederatedSearchService` consumes the semantic projections to provide cross-domain results.
- **UI:** Filament panels consume Eloquent models, providing a curated view of the underlying schemas.

## 6. Diagrams

[Semantic Layer](../assets/semantic-layer.mmd)

```mermaid
---
title: Semantic Layer
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
flowchart TD
    subgraph Physical["Physical Layer (PostgreSQL)"]
        direction TB
        db_c["chinook.*"]
        db_n["northwind.*"]
        db_p["pagila.*"]
    end

    subgraph Semantic["Semantic Layer (Eloquent Models)"]
        direction TB
        M["Canonical Model (UUIDv7)"]
        S["Source Identity (Registry)"]
        P["Product Boundary (Schemas)"]
    end

    subgraph Consumption["Consumption Layer (Filament/Search)"]
        F["Filament Resources"]
        V["Federated Search (RRF)"]
        A["Admin Portfolio Snapshots"]
    end

    Physical --> Semantic
    Semantic --> Consumption

    subgraph Metrics["Metrics & KPIs"]
        Count["Record Counts"]
        Snap["Temporal Snapshots"]
        RRF["Search Relevance Score"]
    end

    Semantic -.-> Metrics
```
