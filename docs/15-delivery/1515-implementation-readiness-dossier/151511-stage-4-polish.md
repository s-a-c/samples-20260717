---
title: Stage 4 — Polish
description: Acceptance Stage 4 (Polish) of the Implementation-Readiness Dossier — gates, checks, operator commands, evidence, and recovery.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [dossier, delivery, acceptance]
created: 2026-08-23
updated: 2026-08-23
---

# Stage 4 — Polish

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

**Risk order:** 4
**Decision reference:** ADR 100326, ADR 100331
**Status:** complete

## 1. Acceptance gates

| Gate                                                                         | Evidence                                             | Check                                               | Status |
| ---------------------------------------------------------------------------- | ---------------------------------------------------- | --------------------------------------------------- | ------ |
| PHPStan level: max baseline citation guard (PhpStanBaselineCitationTest.php) | `tests/Architecture/PhpStanBaselineCitationTest.php` | `php artisan test --filter=PhpStanBaselineCitation` | Pass   |
| 24 Architecture rules (ArchitectureTest.php)                                 | `tests/Architecture/ArchitectureTest.php`            | `composer test:arch`                                | Pass   |
| CI Quality Gate workflow (.github/workflows/tests.yml)                       | `.github/workflows/tests.yml`                        | `GitHub Actions tests.yml green`                    | Pass   |

## 2. Automated checks

- `composer test:unit`
- `composer test:type-cov`

## 3. Operator commands

```bash
composer test --filter=PhpStanBaselineCitation
composer test:arch
git diff --exit-code .github/workflows/tests.yml
```

## 4. Evidence location

- `.github/workflows/tests.yml`
- `tests/Architecture/ArchitectureTest.php`

## 5. Recovery procedure

1. Run `composer test:arch` to confirm all architecture rules pass; cite or resolve any new baseline entry.
2. Re-run the PHPStan baseline citation guard (`composer test --filter=PhpStanBaselineCitation`) so every ignored error remains justified.
3. If the CI Quality Gate workflow regresses, re-run `.github/workflows/tests.yml` locally via `act` or push a fix branch until CI is green.
