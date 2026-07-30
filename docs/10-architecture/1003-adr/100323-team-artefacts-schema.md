---
title: "0012 - Team Artefacts Schema"
description: "Team artefacts (documents, diagrams) need structured storage."
type: adr
tags: \[adr, "0012", team]
updated: 2026-07-30
---

# 0012 - Team Artefacts Schema

## Status: Proposed

## Context

Team artefacts (documents, diagrams) need structured storage.

## Decision

Introduce a migration and model for team_artefacts table with polymorphic relations.

## Consequences

- Centralizes artefact storage
- Enables tagging and search
- Migration needed for existing artefacts
