---
title: "Functional Requirements"
description: "Functional requirements for shared capabilities and isolated sample-product panels."
type: requirements
tags: \[requirements, functional]
updated: 2026-07-30
---

# Functional Requirements

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Requirement Identifiers](#2-requirement-identifiers)
- [3. Functional Requirements](#3-functional-requirements)
    - [3.1. FR-1: Multi-Product Isolation](#31-fr-1-multi-product-isolation)
    - [3.2. FR-2: Authentication \& Authorization](#32-fr-2-authentication--authorization)
    - [3.3. FR-3: Product Lifecycle](#33-fr-3-product-lifecycle)
    - [3.4. FR-4: Search \& Discovery](#34-fr-4-search--discovery)
    - [3.5. FR-5: Portfolio Management](#35-fr-5-portfolio-management)
- [4. Diagrams](#4-diagrams)

</details>

---

## 1. Purpose

This document specifies the functional requirements for the Samples project, categorized by capability area.

## 2. Requirement Identifiers

- `FR-1.x`: Multi-Product Isolation
- `FR-2.x`: Authentication & Authorization
- `FR-3.x`: Product Lifecycle (Import/Reset)
- `FR-4.x`: Search & Discovery
- `FR-5.x`: Portfolio Management

## 3. Functional Requirements

### 3.1. FR-1: Multi-Product Isolation

- **FR-1.1:** The system shall support three independent product domains: Chinook, Northwind, and Pagila.
- **FR-1.2:** Each product domain shall have its own dedicated database schema.
- **FR-1.3:** The system shall provide isolated Filament panels for each product.

### 3.2. FR-2: Authentication & Authorization

- **FR-2.1:** The system shall use Laravel Fortify for centralized authentication.
- **FR-2.2:** The system shall support Passkeys (WebAuthn) for secure login.
- **FR-2.3:** The system shall implement team-based RBAC using Spatie Permission.
- **FR-2.4:** Product Curator roles shall be scoped to specific product panels.

### 3.3. FR-3: Product Lifecycle

- **FR-3.1:** The system shall provide an import pipeline to ingest data into product schemas.
- **FR-3.2:** The system shall support a state-managed reset workflow for each product.
- **FR-3.3:** Reset operations shall require a signed-token confirmation via CLI or UI.

### 3.4. FR-4: Search & Discovery

- **FR-4.1:** The system shall provide a federated search across all product domains.
- **FR-4.2:** Search results shall be ranked using Reciprocal Rank Fusion (RRF).
- **FR-4.3:** The system shall generate and store embeddings for product records using pgvector.

### 3.5. FR-5: Portfolio Management

- **FR-5.1:** The system shall display an administrative portfolio card for all products.
- **FR-5.2:** The portfolio shall show real-time record counts and snapshot status.

## 4. Diagrams

[Functional Capabilities](../assets/functional-capabilities.mmd)

````mermaid
---
title: Functional Capabilities
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
    subgraph Core["Core Capabilities"]
        direction TB
        Auth["Authentication (Fortify/Passkeys)"]
        RBAC["Authorization (Spatie Permission/Shield)"]
        Teams["Team Management (UUIDv7/Polymorphic)"]

        Auth --> RBAC ~~~ Teams
    end

    subgraph Products["Product Domains"]
        direction LR
        Chinook["Chinook Panel"]
        Northwind["Northwind Panel"]
        Pagila["Pagila Panel"]

        Chinook ~~~ Northwind ~~~ Pagila
    end

    subgraph Services["Infrastructure Services"]
        direction TB
        Import["Import Pipeline (Shadow Schemas)"]
        Reset["Reset Semantics (signed tokens)"]
        Search["Federated Search (pgvector/RRF)"]
        Portfolio["Portfolio Snapshots (Admin Page)"]

        Import --> Reset ~~~ Search ~~~ Portfolio
    end

    Core --> Products
    Services --> Products
    Products --> Portfolio```
````
