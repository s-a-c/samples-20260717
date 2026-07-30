---
title: "Stage 3 — Quality & Features"
description: "| Gate | Evidence | Check | Status |"
type: delivery
tags: \[delivery, implementation-readiness-dossier, stage, "3"]
updated: 2026-07-30
---

# Stage 3 — Quality & Features

**Risk order:** 3
**Decision reference:** ADR 100316, ADR 100317, ADR 100319, ADR 100323, ADR 100329
**Status:** complete

## Acceptance gates

| Gate                                                                                 | Evidence                                                                                | Check                                                     | Status |
| ------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------- | --------------------------------------------------------- | ------ |
| Spatie + Shield + Fortify auth matrix tests                                          | `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php`                              | `php artisan test --filter=AuthorizationAcceptanceMatrix` | Pass   |
| Federated Search & RRF tests (FederatedSearchTest.php, ReciprocalRankFusionTest.php) | `tests/Feature/Search/FederatedSearchTest.php, tests/Unit/ReciprocalRankFusionTest.php` | `php artisan test --filter=FederatedSearch`               | Pass   |
| Portfolio Card & Snapshot view (PortfolioTest.php)                                   | `tests/Feature/Filament/PortfolioTest.php`                                              | `php artisan test --filter=Portfolio`                     | Pass   |

## Automated checks

- `composer rector`
- `composer mago:analyze`
- `composer test:mutation`

## Operator commands

```bash
composer test --filter=AuthorizationAcceptanceMatrix
composer test --filter=FederatedSearch
composer test --filter=Portfolio
```

## Evidence location

- `tests/Feature/Search/FederatedSearchTest.php`

## Recovery procedure

1. Run the auth acceptance matrix (`composer test --filter=AuthorizationAcceptanceMatrix`) and restore any lapsed role/permission mapping.
2. Re-run the search suite (`composer test --filter=FederatedSearch`) and confirm RRF ranking output is stable.
3. Re-run the portfolio test and confirm the snapshot view renders without exceptions.
