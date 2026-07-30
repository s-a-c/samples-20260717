---
title: "Implementation-Readiness Dossier — Contents"
description: "This is the version-controlled operational record that maps each"
type: contents
tags: \[contents, implementation-readiness-dossier, implementation, readiness]
updated: 2026-07-30
---

# Implementation-Readiness Dossier — Contents

This is the version-controlled operational record that maps each
approved decision to its acceptance gates, automated checks, operator
commands, evidence location, and recovery procedure.

Generated evidence (CI runs, release artifacts, coverage reports) is
**not** committed here — it lives in CI or release artifacts. This
dossier only indexes where to find it and what each stage requires.

## Stages

An Acceptance Stage is one risk-ordered delivery increment whose
required Acceptance Gates must pass before the next increment begins.

| Stage                        | Scope                                                            | Status   |
| ---------------------------- | ---------------------------------------------------------------- | -------- |
| Stage 1 — Foundation         | ADR recovery, CI, coverage baseline                              | complete |
| Stage 2 — Domain & Resources | Domain structure, architecture rules, Northwind resources        | complete |
| Stage 3 — Quality & Features | Rector, Mago, Infection, Team Artefacts, search, dossier tooling | complete |
| Stage 4 — Polish             | Documentation, unit tests for core services                      | complete |

Copy `151502-stage-template.md` to a numbered stage file
(e.g. `151503-stage-1-foundation.md`) to author a stage.

## Evidence checklist (per stage)

Each Acceptance Gate has named Acceptance Evidence. A stage is ready
when every gate's evidence row resolves:

- [ ] **Decision reference** — ADR number(s) this stage delivers
- [ ] **Acceptance gates** — non-negotiable conditions, each with named evidence
- [ ] **Automated checks** — composer scripts / CI jobs that prove each gate
- [ ] **Operator commands** — commands an operator runs to verify or recover
- [ ] **Evidence location** — URL/path to the generated evidence (CI run, artifact)
- [ ] **Recovery procedure** — what to do when a gate regresses

## Governance

A stage may not begin until the previous stage's gates pass. A gate
that regresses re-opens its stage.
