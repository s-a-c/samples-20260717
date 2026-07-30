---
title: "0017 - Git Branch + PR + Dependency Strategy"
description: "The application needs a standardized Git workflow to manage feature branches, PRs, and dependencies."
type: adr
tags: \[adr, "0017", git]
updated: 2026-07-30
---

# 0017 - Git Branch + PR + Dependency Strategy

## Status: Proposed

## Context

The application needs a standardized Git workflow to manage feature branches, PRs, and dependencies.

## Decision

Implement a branching strategy that enforces dependency tracking via issue IDs and follows a predictable PR process.

## Consequences

- Improves branch/merge hygiene
- Reduces merge conflicts
- Provides clear dependency ownership
- Requires team adherence to new workflow
