---
title: Implementation-Readiness Dossier — Contents
description: Version-controlled operational record mapping each approved decision to its acceptance gates, automated checks, operator commands, evidence location, and recovery procedure.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: contents
tags: [dossier, delivery, acceptance]
created: 2026-08-31
updated: 2026-08-31
---

# Implementation-Readiness Dossier — Contents

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Stages](#1-stages)
- [2. Evidence checklist (per stage)](#2-evidence-checklist-per-stage)
- [3. Current release evidence](#3-current-release-evidence)
- [4. Governance](#4-governance)

</details>

---

This is the version-controlled operational record that maps each
approved decision to its acceptance gates, automated checks, operator
commands, evidence location, and recovery procedure.

Generated evidence (CI runs, release artifacts, coverage reports) is
**not** committed here — it lives in CI or release artifacts. This
dossier only indexes where to find it and what each stage requires.

## 1. Stages

An Acceptance Stage is one risk-ordered delivery increment whose
required Acceptance Gates must pass before the next increment begins.

| Stage                        | Scope                                                            | Status   |
| ---------------------------- | ---------------------------------------------------------------- | -------- |
| Stage 1 — Foundation         | ADR recovery, CI, coverage baseline                              | complete |
| Stage 2 — Domain & Resources | Domain structure, architecture rules, Northwind resources        | complete |
| Stage 3 — Quality & Features | Rector, Mago, Infection, Team Artefacts, search, dossier tooling | complete |
| Stage 4 — Polish             | Documentation, unit tests for core services                      | complete |

Copy `151502-stage-template.md` to a numbered stage file
(e.g. `151504-stage-1-foundation.md`) to author a stage.

## 2. Evidence checklist (per stage)

Each Acceptance Gate has named Acceptance Evidence. A stage is ready
when every gate's evidence row resolves:

- [ ] **Decision reference** — ADR number(s) this stage delivers
- [ ] **Acceptance gates** — non-negotiable conditions, each with named evidence
- [ ] **Automated checks** — composer scripts / CI jobs that prove each gate
- [ ] **Operator commands** — commands an operator runs to verify or recover
- [ ] **Evidence location** — URL/path to the generated evidence (CI run, artifact)
- [ ] **Recovery procedure** — what to do when a gate regresses

## 3. Current release evidence

The current delivery map is [Wayfinder #85](https://github.com/s-a-c/samples-20260717/issues/85),
which intentionally remains open. Its execution mirror is Beads epic
`samples-20260717-7rg`: 20 direct children, all closed, each with a GitHub
reference. The implementation and acceptance evidence is split by state:

- Merged implementation and Linux evidence: PR #107 at
  `420434c8ae1f811d97c34a2d62f222479f02cb51`; PR #110 at
  `4210e5bfaa865e183559a7c81260b555306b85f6`.
- Current Herd evidence: PostgreSQL extensions, migrations, hydrated Pest
  588/588, PHPStan 0 errors, Pint, Mago guard, architecture tests, focused
  import/search/Admin tests, and `pnpm run build` pass locally.
- Current follow-up: the pnpm workflow correction is present in the working
  tree but still needs a committed remote SHA and a fresh GitHub run before
  scheduled TIA and mutation acceptance is current.

The project database target is PostgreSQL 18 with pgvector. SQLite references
in research and superseded ADR history do not describe the application
database.

## 4. Governance

A stage may not begin until the previous stage's gates pass. A gate
that regresses re-opens its stage.
