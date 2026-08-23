---
title: "0012 - Team Artefacts Schema"
description: "Team artefacts (documents, diagrams) need structured storage."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0012", team]
created: 2026-07-25
updated: 2026-08-17
---

# 0012 - Team Artefacts Schema

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Status: Proposed](#1-status-proposed)
- [2. Context](#2-context)
- [3. Decision](#3-decision)
- [4. Consequences](#4-consequences)

</details>

---

## 1. Status: Proposed

## 2. Context

Team artefacts (documents, diagrams) need structured storage.

## 3. Decision

Introduce a migration and model for team_artefacts table with polymorphic relations.

## 4. Consequences

- Centralizes artefact storage
- Enables tagging and search
- Migration needed for existing artefacts
