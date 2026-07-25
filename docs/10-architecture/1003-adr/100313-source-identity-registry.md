---
title: Source Identity Registry
description: Standardized way to identify source systems for sample products
type: architecture
tags: [architecture, design, product]
updated: 2026-07-25
category: documentation
---
# Source Identity Registry
 
## 1.1. Purpose
The application requires a standardized way to identify the source system for all sample products.
 
## 1.2. Definition
Implement a registry service that maps internal identifiers to external source labels.
 
## 1.3. Scope
This applies to Chinook, Northwind, and Pagila domains.
 
## 1.4. Implementation
The registry will be used across all product interaction layers.
 
## 1.5. Consequences
- Enables consistent source tracking.
- May require coordination with external API providers.
 
## 1.6. References
- RFC-0001: Multi-Product Architecture
