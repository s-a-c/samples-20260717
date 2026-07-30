---
title: "System Context"
description: "This document provides a high-level overview of the Samples application, its users, and its external dependencies."
type: architecture
tags: \[architecture, system, context]
updated: 2026-07-30
---

# System Context

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. System Boundaries](#2-system-boundaries)
- [3. External Actors](#3-external-actors)
- [4. External Systems](#4-external-systems)
- [5. Runtime Context](#5-runtime-context)
- [6. Diagrams](#6-diagrams)

</details>

---

## 1. Purpose

This document provides a high-level overview of the Samples application, its users, and its external dependencies.

## 2. System Boundaries

The Samples application is a unified Laravel 13 platform that encapsulates three distinct product domains (Chinook, Northwind, Pagila). It acts as the primary interface for data curation, search, and administrative oversight.

## 3. External Actors

- **Authenticated User:** Any user with valid credentials and team membership.
- **Product Curator:** A specialized user role authorized to manage specific product panels.
- **Operator:** A high-privilege user role with global administrative access.

## 4. External Systems

- **PostgreSQL 18:** The authoritative data store, hosting both shared infrastructure and isolated product schemas.
- **AI Embedding Provider:** External APIs (e.g., Gemini, OpenAI) used to generate vector embeddings for product records.
- **Mail Service:** (Inferred) SMTP or API-based provider for sending invitation and notification emails.

## 5. Runtime Context

The application runs in a PHP 8.5 environment, leveraging Laravel's ecosystem for routing, authentication, and job processing. It expects a PostgreSQL 18 instance with `pgvector` and standard text search extensions enabled.

## 6. Diagrams

[System Context Diagram](../assets/system-context.mmd)

````mermaid
---
title: System Context
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
C4Context
    title System Context diagram for Samples Application

    Person(operator, "Operator", "Administrator managing the application and products.")
    Person(curator, "Curator", "Product-specific data manager.")

    System(app, "Samples Application", "Laravel 13 application providing Chinook, Northwind, and Pagila panels.")

    System_Ext(postgres, "PostgreSQL 18", "Primary data store with schemas for each product.")
    System_Ext(provider, "AI Provider", "Gemini/OpenAI for generating embeddings.")

    Rel(operator, app, "Administers system, views portfolio", "HTTPS")
    Rel(curator, app, "Manages product-specific data", "HTTPS")

    Rel(app, postgres, "Reads/Writes data", "SQL/TCP")
    Rel(app, provider, "Requests embeddings", "HTTPS/API")

    UpdateRelStyle(app, postgres, $offsetX="-80", $offsetY="-10")
    UpdateRelStyle(app, provider, $offsetX="80", $offsetY="10")
    UpdateLayoutConfig($c4ShapeInRow="1", $c4BoundaryInRow="1")```
````
