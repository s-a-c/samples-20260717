---
title: "Security Design"
description: "This document specifies the security architecture of the Samples application, including authentication, authorization, and data protection strategies."
type: design
tags: \[design, security]
updated: 2026-07-30
---

# Security Design

## Purpose

This document specifies the security architecture of the Samples application, including authentication, authorization, and data protection strategies.

## Authentication

- **Provider:** Laravel Fortify provides the backend authentication logic.
- **Passkeys:** The system supports WebAuthn (Passkeys) via `2024_01_01_000000_create_passkeys_table.php`, allowing passwordless authentication.
- **MFA:** Multi-factor authentication is supported for all user accounts.

## Authorization (RBAC)

- **Engine:** Spatie Permission manages roles and permissions.
- **Roles:**
    - `super_admin`: Global access to all panels and system settings.
    - `{product}_curator`: Access restricted to a specific product panel (e.g., `chinook_curator`).
- **Team Isolation:** The `EnsureTeamMembership` middleware ensures that users can only access data belonging to teams they are members of.

## Data Protection

### Schema Isolation

Product domains are isolated at the database schema level. This provides a hard boundary that prevents cross-domain SQL injection or data leakage through Eloquent relationship misconfigurations.

### Signed Tokens

The product reset workflow uses signed tokens to prevent unauthorized or accidental resets. The token includes the product identifier and an expiration timestamp, verified by the `ResetConfirmationService`.

### UUIDv7

The use of UUIDv7 instead of sequential IDs prevents ID enumeration attacks and ensures that primary keys cannot be guessed.

## Trust Boundaries

- **App Boundary:** Encompasses all Laravel code, Filament panels, and services.
- **Data Boundary:** Encompasses the PostgreSQL database and its schema-level access controls.
- **External Boundary:** Encompasses third-party AI providers for embeddings.

## Diagrams

[Security Trust Boundaries](../../assets/security-trust-boundaries.mmd)

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
