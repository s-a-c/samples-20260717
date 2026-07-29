# Wayfinder #15 Compliance Review #3 Remediation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the 4 residual gaps (R1–R4) identified in the [2026-07-29 Wayfinder #15 Compliance Report (Review #3)](../specs/2026-07-29-wayfinder-15-compliance-report.md) to bring static analysis, script runners, dossier evidence, and test coverage to 100% compliance.

**Architecture:** No structural changes. Task 1 resolves 5 PHPStan errors (1 stale baseline entry, 4 test code typing fixes). Task 2 aligns the `test:arch` composer script runner with Pest. Task 3 populates dossier stage files with verified evidence paths and completed statuses. Task 4 expands feature test coverage.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 5, PHPStan (Larastan) `level: max`, Mago, Pint, GitHub Actions, PostgreSQL 18 + `pgvector`.

**Compliance report:** [`docs/superpowers/specs/2026-07-29-wayfinder-15-compliance-report.md`](../specs/2026-07-29-wayfinder-15-compliance-report.md)

## Global Constraints

- Every PHP modification MUST pass `vendor/bin/pint --dirty --format agent` for style compliance.
- Every code change MUST pass `composer types:check` (PHPStan `level: max`) with zero errors.
- Every test file MUST use Pest 5 syntax (`test()` or `it()`).
- No new Composer or NPM packages may be introduced.

---

## File Map

| Task       | Creates / Modifies                                                                                                                  | Responsibility                                                                          |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| **Task 1** | `phpstan-baseline.neon`, `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php`, `tests/Feature/Console/OperatorCreateTest.php` | Fix 5 PHPStan errors so `composer types:check` passes with zero errors                  |
| **Task 2** | `composer.json`                                                                                                                     | Update `"test:arch"` script to call `vendor/bin/pest --testsuite=Architecture` directly |
| **Task 3** | `docs/15-delivery/1515-implementation-readiness-dossier/151503-stage-1-foundation.md` through `151506-stage-4-polish.md`            | Record completed stage statuses and verified evidence paths in dossier files            |
| **Task 4** | `tests/Feature/Import/ProductImportPipelineTest.php`, `tests/Feature/Reset/ResetWindowTest.php`                                     | Add targeted feature tests to advance line coverage ratchet toward 80%                  |

---

## Task 1: Fix PHPStan Static Analysis Errors (R1)

**Files:**

- Modify: `phpstan-baseline.neon:476-481`
- Modify: `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php:22-26`
- Modify: `tests/Feature/Console/OperatorCreateTest.php:49-61`

**Interfaces:**

- Consumes: PHPStan `level: max` rules
- Produces: `composer types:check` exiting 0 with zero errors

- [ ] **Step 1: Write failing assertion test / verify current errors**

Run: `composer types:check`
Expected: 5 errors reported (`ignore.unmatched` in `phpstan-baseline.neon`, 2 `argument.type` in `AuthorizationAcceptanceMatrixTest.php`, 2 `nullsafe.neverNull` in `OperatorCreateTest.php`).

- [ ] **Step 2: Remove stale `ignore.unmatched` entry from `phpstan-baseline.neon`**

Remove lines 476–481 from `phpstan-baseline.neon`:

```yaml
		-
			message: '#^Parameter \#1 \$session of method Illuminate\\Http\\Request\:\:setLaravelSession\(\) expects Illuminate\\Contracts\\Session\\Session, mixed given\.$#'
			identifier: argument.type
			count: 1
			path: tests/Feature/Auth/AuthenticationTest.php
			# bd:wf15-g1-ratchet — argument.type (wayfinder #15 G1; fix in Phase B)
```

- [ ] **Step 3: Fix `argument.type` in `AuthorizationAcceptanceMatrixTest.php`**

In `tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php` (lines 22 and 26), cast `$url` or type-hint as string:

```php
/** @var string $uri */
$uri = route('filament.chinook.resources.artists.index');
$response = $this->get($uri);
```

- [ ] **Step 4: Fix `nullsafe.neverNull` in `OperatorCreateTest.php`**

In `tests/Feature/Console/OperatorCreateTest.php` (lines 49 and 61), replace `?->` with `->` since the receiver is typed or non-null:

```php
expect($operator->email)->toBe('operator@example.com');
```

- [ ] **Step 5: Run `composer types:check` to verify zero errors**

Run: `composer types:check`
Expected: PASS — 0 errors.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add phpstan-baseline.neon tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php tests/Feature/Console/OperatorCreateTest.php
git commit -m "fix(types): resolve 5 PHPStan errors for clean types:check gate (wayfinder #15, R1)"
```

---

## Task 2: Update `test:arch` Script Runner (R2)

**Files:**

- Modify: `composer.json`

**Interfaces:**

- Consumes: Composer script definitions
- Produces: `composer test:arch` running `vendor/bin/pest --testsuite=Architecture` cleanly

- [ ] **Step 1: Inspect `composer.json` script definition**

Current line in `composer.json`:

```json
"test:arch": [
    "@mago:guard",
    "php artisan test --testsuite=Architecture"
]
```

- [ ] **Step 2: Update `test:arch` to use `vendor/bin/pest`**

Update `composer.json`:

```json
"test:arch": [
    "@mago:guard",
    "vendor/bin/pest --testsuite=Architecture"
]
```

- [ ] **Step 3: Run `composer test:arch` to verify clean exit code 0**

Run: `composer test:arch`
Expected: Mago guard passes, 26 Pest architecture tests pass, exit code 0.

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "fix(ci): use pest runner directly in composer test:arch for clean exit status (wayfinder #15, R2)"
```

---

## Task 3: Complete Dossier Stage Evidence & Statuses (R4)

**Files:**

- Modify: `docs/15-delivery/1515-implementation-readiness-dossier/151503-stage-1-foundation.md`
- Modify: `docs/15-delivery/1515-implementation-readiness-dossier/151504-stage-2-domain-resources.md`
- Modify: `docs/15-delivery/1515-implementation-readiness-dossier/151505-stage-3-quality-features.md`
- Modify: `docs/15-delivery/1515-implementation-readiness-dossier/151506-stage-4-polish.md`

**Interfaces:**

- Consumes: ADRs 100301–100337, automated test suites, CI workflow
- Produces: Completed Stage Files with `Status: complete`, verified evidence links, and operational commands

- [ ] **Step 1: Update Stage 1 Foundation (`151503-stage-1-foundation.md`)**

Set `Status: complete`. Populate the Acceptance Gates table with:

- Extension DDL migration `0001_01_01_000000_create_postgres_extensions.php` (Pass)
- Pest test `PostgresExtensionsTest.php` (Pass)
- Artisan check `php artisan pgsql:check` (Pass)
  Set Evidence Location to `.github/workflows/tests.yml` and `tests/Feature/Postgres/PostgresExtensionsTest.php`.

- [ ] **Step 2: Update Stage 2 Domain Resources (`151504-stage-2-domain-resources.md`)**

Set `Status: complete`. Populate Acceptance Gates with:

- UUIDv7 trait verification (`HasUuids` on all models)
- Source Identity Registry (`public.source_identities` uniqueness and JSONB key)
- Shadow schema import pipeline (`ChinookImporter`, `NorthwindImporter`, `PagilaImporter`)
  Set Evidence Location to `tests/Feature/Import/ProductImportPipelineTest.php`.

- [ ] **Step 3: Update Stage 3 Quality & Features (`151505-stage-3-quality-features.md`)**

Set `Status: complete`. Populate Acceptance Gates with:

- Spatie + Shield + Fortify auth matrix tests (`AuthorizationAcceptanceMatrixTest.php`)
- Federated Search & RRF tests (`FederatedSearchTest.php`, `ReciprocalRankFusionTest.php`)
- Portfolio Card & Snapshot view (`PortfolioTest.php`)
  Set Evidence Location to `tests/Feature/Search/FederatedSearchTest.php`.

- [ ] **Step 4: Update Stage 4 Polish (`151506-stage-4-polish.md`)**

Set `Status: complete`. Populate Acceptance Gates with:

- PHPStan `level: max` baseline citation guard (`PhpStanBaselineCitationTest.php`)
- 26 Architecture rules (`ArchitectureTest.php`)
- CI Quality Gate workflow (`.github/workflows/tests.yml`)
  Set Evidence Location to `.github/workflows/tests.yml` and `tests/Architecture/ArchitectureTest.php`.

- [ ] **Step 5: Verify dossier integrity**

Run: `php artisan dossier:generate`
Expected: Dossier files refreshed and valid.

- [ ] **Step 6: Commit**

```bash
git add docs/15-delivery/1515-implementation-readiness-dossier/
git commit -m "docs(dossier): record completed stage statuses and verified evidence paths (wayfinder #15, R4)"
```

---

## Task 4: Expand Feature Test Coverage (R3)

**Files:**

- Modify: `tests/Feature/Import/ProductImportPipelineTest.php`
- Modify: `tests/Feature/Reset/ResetWindowTest.php`

**Interfaces:**

- Consumes: `App\Services\ProductImport\*`, `App\Services\ProductReset\*`
- Produces: Additional test assertions elevating line coverage toward 80%

- [ ] **Step 1: Run coverage analysis**

Run: `composer test:coverage`
Expected: HTML coverage report written to `storage/coverage/index.html`.

- [ ] **Step 2: Add edge-case test in `ProductImportPipelineTest.php`**

Add test covering invalid source format handling or abort path in `ProductImportPipelineTest.php`:

```php
test('import pipeline rejects unknown product name gracefully', function (): void {
    expect(fn () => (new App\Services\ProductImport\ProductImportPipeline())->import('unknown_product'))
        ->toThrow(InvalidArgumentException::class);
});
```

- [ ] **Step 3: Add edge-case test in `ResetWindowTest.php`**

Add test covering `ResetWindow::assertWritable()` when reset run is active in `ResetWindowTest.php`:

```php
test('reset window assertWritable throws when window is open', function (): void {
    // create active reset run
    DB::table('public.reset_runs')->insert([
        'id' => (string) Str::uuid(),
        'product' => 'chinook',
        'status' => 'running',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => (new App\Services\ProductReset\ResetWindow())->assertWritable('chinook'))
        ->toThrow(App\Exceptions\ProductResetWindowOpen::class);
});
```

- [ ] **Step 4: Verify tests pass and coverage is checked**

Run: `composer test:feature && composer test:coverage`
Expected: PASS — Coverage increases.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Import/ProductImportPipelineTest.php tests/Feature/Reset/ResetWindowTest.php
git commit -m "test(coverage): add import and reset edge-case tests to advance coverage ratchet (wayfinder #15, R3)"
```

---

## Self-Review

**1. Spec coverage (report §5 gaps → tasks):**

- R1 (5 PHPStan errors) → Task 1 ✅
- R2 (`test:arch` script runner exit status under Herd) → Task 2 ✅
- R4 (Dossier stage completion & evidence) → Task 3 ✅
- R3 (Coverage ratchet toward 80%) → Task 4 ✅

**2. Ordering / dependencies:** Task 1 lands first to make `composer types:check` green. Task 2 fixes the `test:arch` script runner. Tasks 3 and 4 handle documentation and test coverage expansion.

**3. No placeholders:** Every step contains explicit code, commands, file paths, and test cases.

**4. Execution Handoff:** Offer subagent-driven vs. inline execution choice.
