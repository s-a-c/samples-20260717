---
title: "Backlog"
description: "This document tracks the status of planning tickets and the graduation of fog-of-war items within the Samples implementation effort."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [product, backlog]
created: 2026-07-30
updated: 2026-08-17
---

# Backlog

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Current Frontier (Open Tickets)](#2-current-frontier-open-tickets)
- [3. Decisions So Far](#3-decisions-so-far)
- [4. Not Yet Specified (Fog of War)](#4-not-yet-specified-fog-of-war)
- [5. Out of Scope](#5-out-of-scope)
- [6. Diagrams](#6-diagrams)

</details>

---

## 1. Purpose

This document tracks the status of planning tickets and the graduation of fog-of-war items within the Samples implementation effort.

## 2. Current Frontier (Open Tickets)

| ID    | Type      | Title                                     | Status |
| :---- | :-------- | :---------------------------------------- | :----- |
| #15.1 | Research  | HNSW Index Tuning for pgvector            | Open   |
| #15.2 | Grilling  | RRF Weight Calibration Strategy           | Open   |
| #15.3 | Prototype | Livewire-based Search Result Interleaving | Open   |

## 3. Decisions So Far

- **Decision #1:** Adopt UUIDv7 for all primary keys to ensure global uniqueness.
- **Decision #2:** Use native PostgreSQL schemas for product isolation.
- **Decision #3:** Implement Filament panels per product domain for UX isolation.

## 4. Not Yet Specified (Fog of War)

- **Search Performance at Scale:** Defining the threshold for moving search projections to a dedicated Elasticsearch instance.
- **Multi-region Deployment:** Investigating vector search replication strategies across AWS/Azure regions.

## 5. Out of Scope

- **Direct Source Editing:** Changes to original `.sql` and `.csv` source files via the UI are explicitly ruled out.

## 6. Diagrams

[Backlog Flow](../assets/backlog-flow.mmd)

````mermaid
---
title: Backlog Flow
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
stateDiagram-v2
    [*] --> Specified: Wayfinder Charting
    Specified --> Blocked: Dependency Mapping
    Specified --> Ready: Unblocked (Frontier)

    Ready --> InProgress: Claimed (Assignee)
    InProgress --> Review: Resolution Comment
    Review --> Closed: Answer Recorded

    Closed --> [*]

    Review --> InProgress: Feedback / Grill
    Closed --> Ready: Graduates Fog```
````
