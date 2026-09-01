---
title: "0010 - Embedding Profile + AI SDK"
description: "The application integrates with AI providers for embedding generation."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, "0010", embedding]
created: 2026-07-25
updated: 2026-08-17
---
# 0010 - Embedding Profile + AI SDK

<!-- generated-toc -->
<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. 📄 Status: Proposed](#1--status-proposed)
- [2. 📄 Context](#2--context)
- [3. 📄 Decision](#3--decision)
- [4. 📄 Consequences](#4--consequences)

</details>

---
## 1. 📄 Status: Proposed

## 2. 📄 Context

The application integrates with AI providers for embedding generation.

## 3. 📄 Decision

Create an embedding profile abstraction to support multiple AI SDKs.

## 4. 📄 Consequences

- Decouples application from specific AI provider APIs.
- Simplifies future migration.
