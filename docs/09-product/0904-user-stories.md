---
title: "User Stories"
description: "User stories describing curator, operator, and product-specific workflows."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [product, user, stories]
created: 2026-07-30
updated: 2026-08-17
---
# User Stories

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Purpose](#1--purpose)
- [2. 📄 Actors](#2--actors)
- [3. 📄 User Stories](#3--user-stories)
  - [3.1. 📄 US-1: Product Domain Exploration](#31--us-1-product-domain-exploration)
  - [3.2. 📄 US-2: Federated Search](#32--us-2-federated-search)
  - [3.3. 📄 US-3: Product Reset](#33--us-3-product-reset)
- [4. 📄 Diagrams](#4--diagrams)

</details>

---
## 1. 📄 Purpose

This document defines the core user stories and acceptance criteria for the Samples project, mapping user needs to functional requirements.

## 2. 📄 Actors

- **Product Curator:** Responsible for managing data within a specific product domain (e.g., Chinook Curator).
- **Operator:** Responsible for global system administration, portfolio oversight, and product resets.

## 3. 📄 User Stories

### 3.1. 📄 US-1: Product Domain Exploration

**As a** Product Curator,
**I want to** navigate through my assigned product panel and manage domain-specific resources,
**So that** I can maintain high-quality reference data for my domain.

- **Acceptance Criteria:**
    - **AC-1.1:** Access to the panel is restricted to users with the `{product}_curator` role.
    - **AC-1.2:** All domain resources (e.g., Chinook Artists) are editable via standard Filament forms.
    - **AC-1.3:** Changes are logged via Spatie Activitylog.

### 3.2. 📄 US-2: Federated Search

**As a** User,
**I want to** search for a term across all product domains from a single interface,
**So that** I can find relevant records regardless of which product they belong to.

- **Acceptance Criteria:**
    - **AC-2.1:** The search interface accepts natural language queries.
    - **AC-2.2:** Results include matches from Chinook, Northwind, and Pagila.
    - **AC-2.3:** Results are ranked using RRF (Reciprocal Rank Fusion) combining text and vector search.
    - **AC-2.4:** Clicking a result deep-links to the relevant resource in its respective panel.

### 3.3. 📄 US-3: Product Reset

**As an** Operator,
**I want to** reset a product domain to its baseline state,
**So that** I can clear experimental changes and restore the reference data integrity.

- **Acceptance Criteria:**
    - **AC-3.1:** The reset process requires a signed-token confirmation.
    - **AC-3.2:** The reset operation is atomic at the schema level.
    - **AC-3.3:** The system status shows "Resetting" during the operation and blocks concurrent edits.

## 4. 📄 Diagrams

[Primary User Journey](../assets/primary-user-journey.mmd)

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
journey
    title Primary User Journey: Data Curation & Search
    section Authentication
      Visit Site: 5: User
      Login (Fortify): 4: User, App
    section Exploration
      View Dashboard: 5: User, App
      Navigate to Chinook Panel: 4: User, Filament
      List Artists: 5: User, Filament
    section Search
      Enter Search Query: 5: User
      View Federated Results: 4: User, SearchService
      Click Deep Link: 5: User, App
    section Management (Curator)
      Edit Album: 4: Curator, Filament
      Trigger Embedding Update: 3: App, AI Provider
    section Admin (Operator)
      View Portfolio Snapshots: 5: Operator, AdminPanel
      Confirm Product Reset: 4: Operator, ResetService```
````
