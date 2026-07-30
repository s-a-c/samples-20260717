---
title: "Non-functional Requirements"
description: "Quality, security, performance, and operational requirements for the application."
type: requirements
tags: \[requirements, non, functional]
updated: 2026-07-30
---

# Non-functional Requirements

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Requirement Identifiers](#2-requirement-identifiers)
- [3. Non-functional Requirements](#3-non-functional-requirements)
    - [3.1. NFR-1: Reliability \& Durability](#31-nfr-1-reliability--durability)
    - [3.2. NFR-2: Performance \& Scalability](#32-nfr-2-performance--scalability)
    - [3.3. NFR-3: Security \& Compliance](#33-nfr-3-security--compliance)
    - [3.4. NFR-4: Maintainability \& Quality](#34-nfr-4-maintainability--quality)
- [4. Diagrams](#4-diagrams)

</details>

---

## 1. Purpose

This document specifies the quality attributes, constraints, and operational requirements for the Samples project.

## 2. Requirement Identifiers

- `NFR-1.x`: Reliability & Durability
- `NFR-2.x`: Performance & Scalability
- `NFR-3.x`: Security & Compliance
- `NFR-4.x`: Maintainability & Quality

## 3. Non-functional Requirements

### 3.1. NFR-1: Reliability & Durability

- **NFR-1.1:** The system shall use PostgreSQL 18 with native UUIDv7 for all primary keys to ensure global uniqueness and sortability.
- **NFR-1.2:** The import pipeline shall be idempotent and support resumption after failure.
- **NFR-1.3:** Database integrity shall be maintained via schema-level constraints and foreign keys within product domains.

### 3.2. NFR-2: Performance & Scalability

- **NFR-2.1:** The system shall use PostgreSQL Full Text Search (TSV) and pgvector HNSW indexes for sub-second search latency.
- **NFR-2.2:** Vector embeddings shall be generated asynchronously via Laravel Queues to avoid blocking the main request cycle.
- **NFR-2.3:** The portfolio snapshots shall use materialized views or optimized snapshots for fast administrative overviews.

### 3.3. NFR-3: Security & Compliance

- **NFR-3.1:** All product-specific data shall be isolated in dedicated PostgreSQL schemas to prevent cross-domain leakage.
- **NFR-3.2:** The system shall enforce a strict "Team Membership" middleware for all authenticated routes.
- **NFR-3.3:** Passwords shall be hashed using Argon2id, and MFA shall be available for all users.

### 3.4. NFR-4: Maintainability & Quality

- **NFR-4.1:** The codebase shall maintain zero PHPStan errors at `level: max`.
- **NFR-4.2:** The test suite shall achieve a minimum of 80% line coverage (ratcheting from 25%).
- **NFR-4.3:** All architectural decisions shall be documented as ADRs and cross-referenced in the dossier.

## 4. Diagrams

[Security Trust Boundaries](../assets/security-trust-boundaries.mmd)

```mermaid
---
title: Security Trust Boundaries
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
    subgraph Internet["Public Internet"]
        User((User))
    end

    subgraph AppBoundary["Application Trust Boundary"]
        Fortify[Fortify Authentication]
        Shield[Spatie Shield / RBAC]
        Policy[Eloquent Policies]
    end

    subgraph DataBoundary["Data Trust Boundary"]
        subgraph Schemas["Database Schemas"]
            Public[public schema]
            Chinook[chinook schema]
            Northwind[northwind schema]
            Pagila[pagila schema]
        end
    end

    User -->|HTTPS| Fortify
    Fortify -->|Authenticated| Shield
    Shield -->|Authorized| Policy
    Policy -->|Scoped Access| Schemas

    subgraph External["External Systems"]
        AI[AI Provider API]
    end

    AppBoundary -->|Request Embeddings| AI
```

[Test Strategy](../assets/test-strategy.mmd)

```mermaid
---
title: Test Strategy
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
    subgraph Pyramid["Test Pyramid (ADR 100326)"]
        direction TB
        Unit["Unit Tests (Low-level logic)"]
        Feature["Feature Tests (API/Service integration)"]
        Arch["Architecture Tests (Pest Arch)"]
        Acceptance["Acceptance Tests (Matrix/Journeys)"]

        Unit ~~~ Feature ~~~ Arch ~~~ Acceptance
    end

    subgraph Tools["Testing Toolchain"]
        direction TB
        Pest["Pest 5 (Runner)"]
        Larastan["PHPStan (level: max)"]
        Pint["Laravel Pint (Style)"]
        Mago["Mago (Guard)"]

        Pest ~~~ Larastan ~~~ Pint ~~~ Mago
    end

    Tools --> Pyramid
    Pyramid --> CI["CI Quality Gate (.github/workflows/tests.yml)"]
```
