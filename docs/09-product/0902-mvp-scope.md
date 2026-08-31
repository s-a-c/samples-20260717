---
title: "MVP Scope"
description: "Minimum viable product scope for the Samples application."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [product, mvp, scope]
created: 2026-07-30
updated: 2026-08-17
---
# MVP Scope

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Purpose](#1--purpose)
- [2. 📄 Included Capabilities](#2--included-capabilities)
  - [2.1. 📄 Multi-Product Foundations](#21--multi-product-foundations)
  - [2.2. 📄 Domain Panels (Filament)](#22--domain-panels-filament)
  - [2.3. 📄 Shared Services](#23--shared-services)
- [3. 📄 Excluded Capabilities](#3--excluded-capabilities)
- [4. 📄 Deferred (Future)](#4--deferred-future)
- [5. 📄 Diagrams](#5--diagrams)

</details>

---
## 1. 📄 Purpose

This document defines the boundaries of the Minimum Viable Product (MVP) for the Samples project, specifying what is included, excluded, and deferred.

## 2. 📄 Included Capabilities

### 2.1. 📄 Multi-Product Foundations

- **Isolated Schemas:** Native PostgreSQL schemas for Chinook, Northwind, and Pagila.
- **UUIDv7 Primary Keys:** Global uniqueness and temporal sortability across all domains.
- **Source Identity Registry:** Tracking of original record identities from external source files.

### 2.2. 📄 Domain Panels (Filament)

- **Admin Panel:** Centralized dashboard for cross-product portfolio management and system administration.
- **Chinook Panel:** 8 resources for music media management (Artists, Albums, Tracks, etc.).
- **Northwind Panel:** 7 resources for supply chain management (Products, Orders, Customers, etc.).
- **Pagila Panel:** 10 resources for DVD rental management (Films, Actors, Inventory, etc.).

### 2.3. 📄 Shared Services

- **Federated Search:** Unified search interface using RRF to combine TSV and pgvector results.
- **Product Reset:** Signed-token workflow for resetting specific product domains to their baseline.
- **Auth & RBAC:** Fortify-based login with Passkey support and Spatie-based role management.

## 3. 📄 Excluded Capabilities

- **Cross-Product Data Linking:** Foreign keys or semantic relationships between distinct product domains.
- **Native Filament Tenancy:** The application uses isolated panels rather than Filament's built-in multi-tenancy.
- **Public API Access:** The search and domain resources are accessible via the Filament UI only; a public REST API is out of scope.

## 4. 📄 Deferred (Future)

- **Real-time Collaboration:** Presence indicators or shared editing sessions within panels.
- **Automated Data Synthesis:** AI-driven generation of new sample records based on existing domain patterns.

## 5. 📄 Diagrams

[MVP Scope](../assets/mvp-scope.mmd)

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
flowchart TD
    subgraph InScope["MVP: In-Scope"]
        direction TD
        P1["Chinook Media Management"]
        P2["Northwind Supply Chain"]
        P3["Pagila Video Rental"]
        Infra["PostgreSQL 18 + UUIDv7"]
        Auth["Fortify Auth + RBAC"]
        Search["Federated Search + RRF"]
        Admin["Admin Portfolio View"]
    end

    subgraph OutOfScope["Future: Out-of-Scope"]
        direction TD
        E1["Cross-product Data Sync"]
        E2["Multi-tenancy (Filament native)"]
        E3["Direct Data Editing (Source Schemas)"]
    end

    InScope -.-> OutOfScope```
````
