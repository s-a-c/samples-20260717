---
title: "Test Strategy"
description: "This document defines the testing strategy for the Samples project, outlining the test pyramid, toolchain, and quality gates."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: guide
tags: [testing, test, strategy]
created: 2026-07-30
updated: 2026-08-17
---

# Test Strategy

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Purpose](#1-purpose)
- [2. Test Pyramid](#2-test-pyramid)
    - [2.1. Unit Tests](#21-unit-tests)
    - [2.2. Feature Tests](#22-feature-tests)
    - [2.3. Architecture Tests](#23-architecture-tests)
    - [2.4. Acceptance Tests](#24-acceptance-tests)
- [3. Quality Gates](#3-quality-gates)
    - [3.1. CI Workflow](#31-ci-workflow)
- [4. Toolchain](#4-toolchain)
- [5. Diagrams](#5-diagrams)

</details>

---

## 1. Purpose

This document defines the testing strategy for the Samples project, outlining the test pyramid, toolchain, and quality gates.

## 2. Test Pyramid

Following **ADR 0013**, the project employs a balanced test pyramid:

### 2.1. Unit Tests

- **Scope:** Individual classes, Value Objects, and isolated logic (e.g., `ResetEvidence`, `ReciprocalRankFusion`).
- **Tool:** Pest 5.
- **Location:** `tests/Unit/`.

### 2.2. Feature Tests

- **Scope:** API interactions, service integration, and database operations.
- **Key Suites:**
    - `ProductImportPipelineTest`: Verifies data ingestion.
    - `FederatedSearchTest`: Verifies RRF and vector search results.
    - `ResetWindowTest`: Verifies the state machine and signed tokens.
- **Location:** `tests/Feature/`.

### 2.3. Architecture Tests

- **Scope:** Enforcing coding standards and architectural boundaries.
- **Tool:** Pest Arch.
- **Rules:** 26 rules defined in `tests/Architecture/ArchitectureTest.php` (e.g., Models must use `HasUuids`, Services must be final).

### 2.4. Acceptance Tests

- **Scope:** Full-stack user journeys and authorization matrices.
- **Location:** `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php`.

## 3. Quality Gates

### 3.1. CI Workflow

The `.github/workflows/tests.yml` enforces the following gates on every Pull Request:

1. **Style:** Laravel Pint (`composer lint`).
2. **Static Analysis:** PHPStan at `level: max` (`composer types:check`).
3. **Architecture:** Pest Arch (`composer test:arch`).
4. **Functional:** Pest test suite with 100% pass requirement.
5. **Coverage:** Minimum line coverage floor (ratcheting toward 80%).

## 4. Toolchain

- **Runner:** Pest 5.
- **Static Analysis:** Larastan (PHPStan).
- **Linter:** Laravel Pint.
- **Guard:** Mago.

## 5. Diagrams

[Test Strategy](../assets/test-strategy.mmd)

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
    subgraph Pyramid["Test Pyramid (ADR 100326)"]
        direction BT
        Unit["Unit Tests (Low-level logic)"]
        Feature["Feature Tests (API/Service integration)"]
        Arch["Architecture Tests (Pest Arch)"]
        Acceptance["Acceptance Tests (Matrix/Journeys)"]
    end

    subgraph Tools["Testing Toolchain"]
        direction LR
        Pest["Pest 5 (Runner)"]
        Larastan["PHPStan (level: max)"]
        Pint["Laravel Pint (Style)"]
        Mago["Mago (Guard)"]
    end

    Tools --> Pyramid
    Pyramid --> CI["CI Quality Gate (.github/workflows/tests.yml)"]```
````
