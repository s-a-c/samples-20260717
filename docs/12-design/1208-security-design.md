---
title: "Security Design"
description: "Documentation page for 1208-security-design."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: guide
tags: [documentation]
created: 2026-08-17
updated: 2026-08-17
---

# Security Design

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Authentication](#2-authentication)
- [3. Authorization (RBAC)](#3-authorization-rbac)
- [4. Data Protection](#4-data-protection)
    - [4.1. Schema Isolation](#41-schema-isolation)
    - [4.2. Signed Tokens](#42-signed-tokens)
    - [4.3. UUIDv7](#43-uuidv7)
- [5. Trust Boundaries](#5-trust-boundaries)
- [6. Diagrams](#6-diagrams)

</details>

---

## 1. Purpose

This document specifies the security architecture of the Samples application, including authentication, authorization, and data protection strategies.

## 2. Authentication

- **Provider:** Laravel Fortify provides the backend authentication logic.
- **Passkeys:** The system supports WebAuthn (Passkeys) via `2024_01_01_000000_create_passkeys_table.php`, allowing passwordless authentication.
- **MFA:** Multi-factor authentication is supported for all user accounts.

## 3. Authorization (RBAC)

- **Engine:** Spatie Permission manages roles and permissions.
- **Roles:**
    - `super_admin`: Global access to all panels and system settings.
    - `{product}_curator`: Access restricted to a specific product panel (e.g., `chinook_curator`).
- **Team Isolation:** The `EnsureTeamMembership` middleware ensures that users can only access data belonging to teams they are members of.

## 4. Data Protection

### 4.1. Schema Isolation

Product domains are isolated at the database schema level. This provides a hard boundary that prevents cross-domain SQL injection or data leakage through Eloquent relationship misconfigurations.

### 4.2. Signed Tokens

The product reset workflow uses signed tokens to prevent unauthorized or accidental resets. The token includes the product identifier and an expiration timestamp, verified by the `ResetConfirmationService`.

### 4.3. UUIDv7

The use of UUIDv7 instead of sequential IDs prevents ID enumeration attacks and ensures that primary keys cannot be guessed.

## 5. Trust Boundaries

- **App Boundary:** Encompasses all Laravel code, Filament panels, and services.
- **Data Boundary:** Encompasses the PostgreSQL database and its schema-level access controls.
- **External Boundary:** Encompasses third-party AI providers for embeddings.

## 6. Diagrams

[Security Trust Boundaries](../assets/security-trust-boundaries.mmd)

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

    AppBoundary -->|Request Embeddings| AI```
````
