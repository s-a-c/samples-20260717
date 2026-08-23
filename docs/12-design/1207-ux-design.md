---
title: "UX Design"
description: "This document describes the user experience principles, navigation patterns, and panel structures of the Samples application."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [design, ux]
created: 2026-07-30
updated: 2026-08-17
---

# UX Design

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Design Principles](#2-design-principles)
- [3. Navigation Patterns](#3-navigation-patterns)
    - [3.1. Dashboard](#31-dashboard)
    - [3.2. Filament Panels](#32-filament-panels)
    - [3.3. Federated Search UI](#33-federated-search-ui)
- [4. Key UX Flows](#4-key-ux-flows)
    - [4.1. Cross-Product Discovery](#41-cross-product-discovery)
    - [4.2. Product State Management](#42-product-state-management)
- [5. Diagrams](#5-diagrams)

</details>

---

## 1. Purpose

This document describes the user experience principles, navigation patterns, and panel structures of the Samples application.

## 2. Design Principles

- **Isolation by Default:** Each product domain has its own visual boundary (Panel) to avoid user confusion between heterogeneous datasets.
- **Shared Infrastructure:** Common tasks like login, search, and profile management use consistent patterns across all panels.
- **Administrative Oversight:** The Admin panel provides a "God-eye" view of all product domains without merging their data.

## 3. Navigation Patterns

### 3.1. Dashboard

The root dashboard serves as the central hub, providing entry points to the Admin, Chinook, Northwind, and Pagila panels.

### 3.2. Filament Panels

Each product panel is a dedicated Filament instance:

- **Chinook:** Blue theme, music-focused icons.
- **Northwind:** Green theme, industrial icons.
- **Pagila:** Purple theme, media/entertainment icons.
- **Admin:** Gray/Slate theme, administrative icons.

### 3.3. Federated Search UI

The global search interface provides a unified results list. Each result includes a badge indicating its product domain and a direct link to the record's resource page.

## 4. Key UX Flows

### 4.1. Cross-Product Discovery

1. User activates search from any panel.
2. Results display matches from all three products.
3. Clicking a result navigates the user to the specific panel and resource for that record.

### 4.2. Product State Management

1. Operator visits the Admin Portfolio.
2. Identifies a product that needs resetting.
3. Triggers the reset workflow, which provides clear visual feedback of the "Resetting" state.

## 5. Diagrams

[UX Flow](../assets/ux-flow.mmd)

```mermaid
---
title: User Experience Flow
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
    Login([Login Page]) --> Dash[Home Dashboard]

    subgraph Panels["Panel Navigation"]
        Dash --> Admin[Admin Panel]
        Dash --> Chinook[Chinook Panel]
        Dash --> Northwind[Northwind Panel]
        Dash --> Pagila[Pagila Panel]
    end

    subgraph AdminFeatures["Admin Capabilities"]
        Admin --> Stats[Portfolio Stats]
        Admin --> Snapshots[Snapshots]
        Admin --> Users[User/Team Management]
    end

    subgraph ProductFeatures["Product Capabilities"]
        Chinook --> CResources[Manage Albums/Artists/Tracks]
        Northwind --> NResources[Manage Orders/Products]
        Pagila --> PResources[Manage Films/Actors]
    end

    subgraph Global["Global Actions"]
        Search[Global Federated Search]
        Reset[Product Reset Workflow]
    end

    Panels --- Global
```
