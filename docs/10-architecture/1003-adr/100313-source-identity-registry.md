---
title: "Source Identity Registry"
description: "Standardized way to identify source systems for sample products"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, architecture, source-identity]
created: 2026-07-25
updated: 2026-08-17
---
# Source Identity Registry

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Purpose](#1--purpose)
- [2. 📄 Definition](#2--definition)
- [3. 📄 Scope](#3--scope)
- [4. 📄 Implementation](#4--implementation)
- [5. 📄 Consequences](#5--consequences)
- [6. 📄 References](#6--references)

</details>

---
## 1. 📄 Purpose

The application requires a standardized way to identify the source system for all sample products.

## 2. 📄 Definition

Implement a registry service that maps internal identifiers to external source labels.

## 3. 📄 Scope

This applies to Chinook, Northwind, and Pagila domains.

## 4. 📄 Implementation

The registry will be used across all product interaction layers.

## 5. 📄 Consequences

- Enables consistent source tracking.
- May require coordination with external API providers.

## 6. 📄 References

- RFC-0001: Multi-Product Architecture
