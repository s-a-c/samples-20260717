---
title: Stage N — {Title}
description: Copyable template for authoring one Implementation-Readiness Dossier Acceptance Stage file.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: reference
tags: [dossier, template, acceptance]
created: 2026-08-23
updated: 2026-08-23
---

# Stage N — {Title}

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Acceptance gates](#1-acceptance-gates)
- [2. Automated checks](#2-automated-checks)
- [3. Operator commands](#3-operator-commands)
- [4. Evidence location](#4-evidence-location)
- [5. Recovery procedure](#5-recovery-procedure)

</details>

---

> Copy this file to the next valid prefixed stage file (file tail divisible
> by neither 3 nor 5, e.g. `151513-stage-N-{slug}.md`) and fill it in.
> A stage is ready when every gate's evidence resolves.

**Risk order:** N
**Decision reference:** ADR-00NN, ADR-00NN
**Status:** _pending_ | _in_progress_ | _accepted_

## 1. Acceptance gates

| Gate              | Evidence         | Check        | Status    |
| ----------------- | ---------------- | ------------ | --------- |
| _named condition_ | _named evidence_ | `composer …` | _pending_ |

## 2. Automated checks

```bash
# Commands that prove this stage's gates
composer ci:check
composer test:coverage
```

## 3. Operator commands

```bash
# Verification / recovery commands an operator can run
```

## 4. Evidence location

- CI run: _link_
- Release artifact: _link_

## 5. Recovery procedure

1. _what to do when a gate regresses_
