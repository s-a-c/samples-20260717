---
title: "0008 - Spatie + Shield + Fortify Coexistence"
description: "Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer."
type: adr
tags: \[adr, "0008", spatie]
updated: 2026-07-30
---

# 0008 - Spatie + Shield + Fortify Coexistence

## Status: Proposed

## Context

Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer.

## Decision

Establish clear boundaries between Shield policies, Spatie roles, and Fortify actions.

## Consequences

- Unifies permission checks.
- Reduces guard conflicts.
