---
title: "0014 - Larastan Target Level + Baseline Policy"
description: "Larastan provides static analysis for the application but requires configuration."
type: adr
tags: \[adr, "0014", larastan]
updated: 2026-07-30
---

# 0014 - Larastan Target Level + Baseline Policy

## Status: Proposed

## Context

Larastan provides static analysis for the application but requires configuration.

## Decision

Set Larastan to latest target level and create baseline ignore rules.

## Consequences

- Improves code quality
- Reduces false positives
- Guides development team

## Monitoring

Track the maximum level in git history to ensure gradual increases
