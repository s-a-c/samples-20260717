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
- [3. Governance](#3-governance)
- [4. Current release evidence](#4-current-release-evidence)

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

| Stage | Scope | Status |
| --- | --- | --- |
| Stage 1 — Foundation | ADR recovery, CI, coverage baseline | complete |
| Stage 2 — Domain & Resources | Domain structure, architecture rules, Northwind resources | complete |
| Stage 3 — Quality & Features | Rector, Mago, Infection, Team Artefacts, search, dossier tooling | complete |
| Stage 4 — Polish | Documentation, unit tests for core services | complete |

Copy `151502-stage-template.md` to a numbered stage file
(e.g. `151504-stage-1-foundation.md`) to author a stage.

- [151502-stage-template.md](151502-stage-template.md)
- [151504-stage-1-foundation.md](151504-stage-1-foundation.md)
- [151507-stage-2-domain-resources.md](151507-stage-2-domain-resources.md)
- [151508-stage-3-quality-features.md](151508-stage-3-quality-features.md)
- [151511-stage-4-polish.md](151511-stage-4-polish.md)
- [151598-index.md](151598-index.md)

## 2. Evidence checklist (per stage)

Each Acceptance Gate has named Acceptance Evidence. A stage is ready
when every gate's evidence row resolves:

- [ ] **Decision reference** — ADR number(s) this stage delivers
- [ ] **Acceptance gates** — non-negotiable conditions, each with named evidence
- [ ] **Automated checks** — composer scripts / CI jobs that prove each gate
- [ ] **Operator commands** — commands an operator runs to verify or recover
- [ ] **Evidence location** — URL/path to the generated evidence (CI run, artifact)
- [ ] **Recovery procedure** — what to do when a gate regresses

## 3. Governance

A stage may not begin until the previous stage's gates pass. A gate
that regresses re-opens its stage.

## 4. Current release evidence

The current delivery target is recorded in GitHub [issue #85](https://github.com/s-a-c/samples-20260717/issues/85), with implementation and Linux acceptance in [PR #126](https://github.com/s-a-c/samples-20260717/pull/126). Local Herd observations are reproducible with `php artisan pgsql:check --no-interaction`, `php artisan source:fetch {product}`, `php artisan product:import {product} --force`, and `php -d memory_limit=2G vendor/bin/pest --compact`; the committed gate ledger in [`GATES.md`](../../../GATES.md) records the corresponding checks and remote evidence locations.
