---
title: "0010 - Embedding Profile + AI SDK"
description: "The application integrates with AI providers for embedding generation."
type: adr
tags: \[adr, "0010", embedding]
updated: 2026-07-30
---

# 0010 - Embedding Profile + AI SDK

## Status: Proposed

## Context

The application integrates with AI providers for embedding generation.

## Decision

Create an embedding profile abstraction to support multiple AI SDKs.

## Consequences

- Decouples application from specific AI provider APIs.
- Simplifies future migration.
