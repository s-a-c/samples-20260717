---
title: "0008 - Spatie + Shield + Fortify Coexistence"
description: "Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0008", spatie]
created: 2026-07-25
updated: 2026-08-17
---

# 0008 - Spatie + Shield + Fortify Coexistence

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

Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer.

## 3. Decision

Establish clear boundaries between Shield policies, Spatie roles, and Fortify actions.

## 4. Consequences

- Unifies permission checks.
- Reduces guard conflicts.
