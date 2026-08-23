---
title: "Domain Ontology"
description: "This document defines the core concepts and entities within the Samples domain, representing the conceptual model of the application independently of the physical implementation."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: reference
tags: [architecture, domain, ontology]
created: 2026-07-30
updated: 2026-08-17
---

# Domain Ontology

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Core Concepts](#2-core-concepts)
    - [2.1. Product Domain](#21-product-domain)
    - [2.2. Sample Entity](#22-sample-entity)
    - [2.3. Team & Membership](#23-team-membership)
- [3. Relationships](#3-relationships)
- [4. Competency Questions](#4-competency-questions)
- [5. Diagrams](#5-diagrams)

</details>

---

## 1. Purpose

This document defines the core concepts and entities within the Samples domain, representing the conceptual model of the application independently of the physical implementation.

## 2. Core Concepts

### 2.1. Product Domain

A top-level classification of reference data. The system currently recognizes three domains:

- **Media (Chinook):** Concepts related to music, artists, albums, and digital media sales.
- **Supply Chain (Northwind):** Concepts related to products, suppliers, customers, and orders.
- **Rental (Pagila):** Concepts related to films, actors, stores, and DVD rentals.

### 2.2. Sample Entity

Any record within a product domain that represents a reference data point. All Sample Entities share common characteristics:

- **Global Identity:** A UUIDv7 identifier unique across the entire system.
- **Source Traceability:** A mapping back to the original identity in the reference dataset.
- **Searchability:** A projection into the federated search index.

### 2.3. Team & Membership

The organizational unit for user access. A Team owns Artefacts and defines the boundary for user collaboration within panels.

## 3. Relationships

- A **Team** can access multiple **Product Domains**.
- A **User** belongs to a **Team** via **Membership**.
- An **Operator** manages all **Product Domains**.
- A **Curator** manages a specific **Product Domain**.
- **Sample Entities** are grouped into **Product Domains** based on their schema location.

## 4. Competency Questions

- Which product domain does this record belong to?
- Who is the curator responsible for this specific album?
- What was the original ID of this customer in the Northwind CSV file?
- Which teams have access to the Pagila rental panel?

## 5. Diagrams

[Domain Ontology](../assets/domain-ontology.mmd)

```mermaid
---
title: Domain Ontology
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
classDiagram
    class SampleProduct {
        <<Enumeration>>
        CHINOOK
        NORTHWIND
        PAGILA
    }

    class DomainModel {
        <<Interface>>
        +UUIDv7 id
        +string source_identity
        +BelongsToProduct()
    }

    class MediaProduct {
        +Album[] albums
        +Track[] tracks
    }

    class SupplyChainProduct {
        +Order[] orders
        +Product[] items
    }

    class RentalProduct {
        +Film[] films
        +Rental[] rentals
    }

    DomainModel <|-- MediaProduct : Chinook
    DomainModel <|-- SupplyChainProduct : Northwind
    DomainModel <|-- RentalProduct : Pagila

    class SearchProjection {
        +string document_tsv
        +vector embedding
        +float rank
    }

    DomainModel *-- SearchProjection : projects to
```
