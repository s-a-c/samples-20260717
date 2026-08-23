---
title: "2026-07-31-admin-import-refresh-buttons"
description: "Documentation page for 2026-07-31-admin-import-refresh-buttons."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: spec
tags: [documentation]
created: 2026-07-31
updated: 2026-08-17
---

## Problem Statement

Product data management is entirely CLI-only. A super_admin who wants to reload Chinook, Northwind, or Pagila data must shell into the server, run `product:confirm`, copy a token, then run `product:import --confirm-token=...` — with no feedback in the admin UI about data freshness, import status, or whether an import is already in progress. There is no way to trigger a Product Import or Product Reset from the admin panel, and no way to see current data freshness at a glance.

## Solution

Add two action buttons — **Import Data** and **Refresh Stats** — to each product card in the Admin panel (`/admin`). The Import Data button triggers the Shadow-Schema Import Pipeline via a queue job, with a confirmation modal showing the source commit SHA and current stats. The Refresh Stats button re-reads the `product_portfolio_snapshots` database view and surfaces freshness with a timestamp and visual change indicators. A polling status badge on each card shows the current Reset Run state (idle, importing, succeeded, failed).

Both the Portfolio page (`/admin/portfolio`) and the Admin dashboard display the same shared card component with both buttons. Non-`super_admin` roles do not see the Import Data button.

## User Stories

1. As a `super_admin`, I want to click an **Import Data** button on any product card, so that I can trigger a Product Import without leaving the admin UI.
2. As a `super_admin`, I want to see a **confirmation modal** when I click Import Data, so that I can review the product name, source commit SHA, and current stats before confirming.
3. As a `super_admin`, I want the confirmation modal to show a **warning** that the operation replaces all live data for that product, so that I am aware of the destructive nature of the action.
4. As a `super_admin`, I want to see a **"Import started" notification** immediately after confirming, so that I know the operation was accepted.
5. As a `super_admin`, I want the Import Data button to show a **spinner/disabled state** while the import is in progress, so that I do not attempt to trigger a second import.
6. As a `super_admin`, I want to see a **status badge** on each product card that shows the current Reset Run state (idle, importing, succeeded, failed), so that I can monitor active and past imports at a glance.
7. As a `super_admin`, I want the status badge to **poll automatically**, so that it stays current without manual page reloads.
8. As a `super_admin`, I want to see a **"Import completed" notification** on my next Filament page load when an import finishes, so that I am informed even if I navigated away.
9. As a `super_admin`, I want to see a **"Import failed" notification and a red status badge** when an import fails, so that I can take action.
10. As a `super_admin`, I want the stats to **auto-refresh** after a successful import, so that I immediately see the updated dataset figures.
11. As a `super_admin`, I want to click a **Refresh Stats** button on any product card, so that I can manually trigger a re-read of the `product_portfolio_snapshots` view.
12. As a `super_admin`, I want to see a **spinner** on the stats area while a refresh is in progress, so that I know the operation is working.
13. As a `super_admin`, I want to see a **"Last refreshed: X ago" timestamp** under the stats after the first refresh, so that I can judge data freshness.
14. As a `super_admin`, I want stats that **changed value since the last refresh** to get a brief green pulse animation, so that I can visually spot what changed without comparing numbers.
15. As a `super_admin`, I want the Refresh Stats button to work **independently per product**, so that I can refresh one product without affecting the others.
16. As a `super_admin`, I want to see the **Import Data button only if I have the `super_admin` role**, so that non-admin users are not presented with an action they cannot perform.
17. As a `curator` (non-super_admin), I want the portfolio cards to still display stats and the Refresh Stats button, so that I can see data freshness even though I cannot trigger imports.
18. As a `super_admin`, I want to trigger an import on a product while another product is actively being imported, so that I can work on multiple products independently.
19. As a `super_admin`, I want to see an error notification if I try to import a product that already has an active Reset Run, so that I understand why the action was rejected.
20. As a `super_admin`, I want to see the **same card component with the same buttons on both the Portfolio page and the Admin dashboard**, so that the experience is consistent regardless of which admin page I am on.

## Implementation Decisions

### Shared Card Component

- The existing `ProductPortfolioCard` widget becomes the **single shared card component** for both the Portfolio page and the Admin dashboard.
- The Portfolio page (`/admin/portfolio`) is refactored to use this widget instead of its current hardcoded STATS array.
- Both surfaces display the same component — the Portfolio page via a page-level widget layout, the dashboard via its existing widget registration.

### Import Mechanism

- A new **`ProductImportJob`** (`ShouldQueue`) handles the actual import. Its `handle()` resolves `ProductImportPipeline` from the container and calls `->run($product)`.
- A **Filament Action** (`Action::make('import')`) triggers the flow:
    1. `requiresConfirmation()` modal showing product name, commit SHA from the Pin Manifest (`database/sources/{product}.php`), current stats snapshot, and a warning ("This will replace all live {product} data").
    2. On confirm: `ResetWindow::assertWritable()` → `ProductImportJob::dispatch($product)` → `Notification::make()->success("Import started for {product}")`.
    3. If `ResetWindow::assertWritable()` throws `ProductResetWindowOpen`: catch it, show error notification, keep button enabled.
- The **Fab!n**
  The **FilamentShield permission gate** (`product::import`) controls visibility of the Import Data button — only `super_admin` role has this permission.
- The existing CLI confirmation token flow (`ResetConfirmationService`) is **not used in the web path**. The authenticated session + FilamentShield gate + confirmation modal replaces the token protocol.

### Queue Configuration

- The **database queue driver** is used (existing `QUEUE_CONNECTION=database`).
- `DB_QUEUE_RETRY_AFTER` is increased to **600 seconds** (10 minutes) to accommodate import duration.
- An optional dedicated `imports` queue can be used to isolate import jobs from the default queue.

### Stats Refresh

- A **Filament Action** (`Action::make('refreshStats')`) triggers the refresh:
    1. On click: show spinner on stats area, re-read `product_portfolio_snapshots` view via `PortfolioSnapshotStats`, update the displayed stats.
    2. Show **"Last refreshed: X ago"** timestamp below the stats.
    3. Stats that changed since the last refresh get a **brief green pulse animation** (CSS animation class toggled by Livewire).
    4. Each product's refresh is independent — clicking "Refresh Stats" on Chinook does not affect Northwind or Pagila.

### Status Badge (Polling)

- A **status badge** is added to each product card, polling `ResetRun::latest('created_at')` for the product.
- States: no badge (no recent runs), green checkmark (succeeded), red X (failed), spinning (running/pending/recovering).
- The badge polls via Livewire's `poll` directive (default 5-second interval when a run is active).
- On import completion (succeeded status detected by poll), the badge triggers an automatic stats refresh.

### Auto-refresh After Import

- When the status badge detects a `succeeded` Reset Run for the product, it automatically triggers the same refresh path as the Refresh Stats button.
- This avoids the user needing to click Refresh after every import.

### Role-Based Access Control

- A FilamentShield permission `product::import` is created and assigned to the `super_admin` role.
- The Import Data action checks `->visible(fn (): bool => auth()->user()->hasPermission('product::import'))`.
- The Refresh Stats action has no permission gate — any authenticated user can refresh stats.
- The existing curator roles (`chinook_curator`, `northwind_curator`, `pagila_curator`) remain read-only — they see stats and the Refresh button but not the Import button.

## Testing Decisions

- **Good tests** verify external behavior (button renders, modal shows, job dispatches, notification appears, badge updates) without testing implementation details (which Livewire method was called, what CSS class was toggled).

### Test File 1: `tests/Feature/Import/ProductImportJobTest.php`

**What it tests:**

- The job dispatches with the correct product name.
- The job's `handle()` calls `ProductImportPipeline::run($product)`.
- The job handles pipeline failures gracefully (pipeline throws → job does not re-throw).
- The job does not require `SerializesModels` (product name is a scalar).

**Prior art:** `tests/Feature/Import/ProductImportPipelineTest.php` already tests the pipeline. This file wraps it in the queue job contract.

### Test File 2: `tests/Feature/Admin/ProductCardActionsTest.php`

**What it tests:**

- The Import Data button is visible to `super_admin` and hidden from other roles.
- The confirmation modal renders with product name and commit SHA.
- Confirming the modal dispatches `ProductImportJob`.
- The "Import started" notification is sent after dispatching.
- Attempting to import while a Reset Run is active shows an error notification.
- The Refresh Stats button refreshes the stats display.
- The "Last refreshed" timestamp appears after a refresh.
- The status badge polls and shows the correct Reset Run state.
- Stats auto-refresh after a succeeded Reset Run is detected.

**Prior art:** This is the first set of Filament action tests in the codebase. They follow the same `\Tests\TestCase` + `RefreshDatabase` pattern as existing Feature tests (`tests/Feature/Import/`, `tests/Feature/Reset/`). Filament actions are tested by mounting them as Livewire components and asserting against notifications, dispatched jobs, and view state.

### Existing Test Integration

- `tests/Feature/Reset/ResetWindowTest.php` already covers the concurrency gate that blocks duplicate imports per product — no changes needed.
- Pipeline and importer tests already exist — no changes needed.

## Out of Scope

- Adding import/refresh buttons to the Chinook, Northwind, or Pagila product-specific panels — this effort is **Admin panel only**.
- Cross-product bulk import — one product at a time.
- Full real-time progress streaming via Reverb or websockets — min viable is triggered import with notification of completion and polling badge.
- Async job progress within the import (e.g., step-by-step log in the UI) — the badge shows succeeded/failed only.
- The CLI `product:import` and `product:confirm` commands remain unchanged.
- Redis or Horizon setup — the database queue driver is sufficient for MVP.
- Email or Slack notifications for import completion.
- Audit log for import actions (beyond the existing `ResetRun` record).

## Further Notes

- This spec implements the decisions from Wayfinder map [#64](https://github.com/s-a-c/samples-20260717/issues/64), which resolved 5 decision tickets covering surfaces, import mechanism, permissions, refresh behavior, and import UX.
- All 5 tickets ([#65](https://github.com/s-a-c/samples-20260717/issues/65), [#66](https://github.com/s-a-c/samples-20260717/issues/66), [#67](https://github.com/s-a-c/samples-20260717/issues/67), [#68](https://github.com/s-a-c/samples-20260717/issues/68), [#69](https://github.com/s-a-c/samples-20260717/issues/69)) are closed with resolution comments containing the full decision context.
- The `product_portfolio_snapshots` database view already exists and aggregates live row counts per product domain.
- The `PortfolioSnapshotStats` service already provides `byProduct()` for reading the view.
- The `ResetWindow` class and `ProductResetWindowOpen` exception already enforce per-product concurrency.
- The `ProductImportPipeline` and per-product importers already exist with full test coverage.
- ADR 100322 (Portfolio Card Architecture) is superseded by this spec's decision to consolidate the two card implementations into one shared component.
