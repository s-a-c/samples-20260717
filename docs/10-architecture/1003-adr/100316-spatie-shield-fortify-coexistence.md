# 0008 - Spatie + Shield + Fortify Coexistence
 
## Status: Proposed
 
## Context
Laravel Shield, Spatie permissions, and Fortify must coexist in the auth layer.
 
## Decision
Establish clear boundaries between Shield policies, Spatie roles, and Fortify actions.
 
## Consequences
- Unifies permission checks.
- Reduces guard conflicts.
