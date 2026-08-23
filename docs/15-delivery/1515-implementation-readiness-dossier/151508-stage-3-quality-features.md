---
title: Stage 3 — Quality & Features
description: Acceptance Stage 3 (Quality & Features) of the Implementation-Readiness Dossier — gates, checks, operator commands, evidence, and recovery.
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [dossier, delivery, acceptance]
created: 2026-08-23
updated: 2026-08-23
---

# Stage 3 — Quality & Features

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

**Risk order:** 3
**Decision reference:** ADR 100316, ADR 100317, ADR 100319, ADR 100323, ADR 100329
**Status:** complete

## 1. Acceptance gates

| Gate                                                                                 | Evidence                                                                                | Check                                                     | Status |
| ------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------- | --------------------------------------------------------- | ------ |
| Spatie + Shield + Fortify auth matrix tests                                          | `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php`                              | `php artisan test --filter=AuthorizationAcceptanceMatrix` | Pass   |
| Federated Search & RRF tests (FederatedSearchTest.php, ReciprocalRankFusionTest.php) | `tests/Feature/Search/FederatedSearchTest.php, tests/Unit/ReciprocalRankFusionTest.php` | `php artisan test --filter=FederatedSearch`               | Pass   |
| Portfolio Card & Snapshot view (PortfolioTest.php)                                   | `tests/Feature/Filament/PortfolioTest.php`                                              | `php artisan test --filter=Portfolio`                     | Pass   |

## 2. Automated checks

- `composer rector`
- `composer mago:analyze`
- `composer test:mutation`

## 3. Operator commands

```bash
composer test --filter=AuthorizationAcceptanceMatrix
composer test --filter=FederatedSearch
composer test --filter=Portfolio
```

## 4. Evidence location

- `tests/Feature/Search/FederatedSearchTest.php`

## 5. Recovery procedure

1. Run the auth acceptance matrix (`composer test --filter=AuthorizationAcceptanceMatrix`) and restore any lapsed role/permission mapping.
2. Re-run the search suite (`composer test --filter=FederatedSearch`) and confirm RRF ranking output is stable.
3. Re-run the portfolio test and confirm the snapshot view renders without exceptions.
