---
title: "0014 - Larastan Target Level + Baseline Policy"
description: "Larastan provides static analysis for the application but requires configuration."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0014", larastan]
created: 2026-07-25
updated: 2026-08-17
---

# 0014 - Larastan Target Level + Baseline Policy

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Status: Proposed](#1-status-proposed)
- [2. Context](#2-context)
- [3. Decision](#3-decision)
- [4. Consequences](#4-consequences)
- [5. Monitoring](#5-monitoring)

</details>

---

## 1. Status: Proposed

## 2. Context

Larastan provides static analysis for the application but requires configuration.

## 3. Decision

Set Larastan to latest target level and create baseline ignore rules.

## 4. Consequences

- Improves code quality
- Reduces false positives
- Guides development team

## 5. Monitoring

Track the maximum level in git history to ensure gradual increases
