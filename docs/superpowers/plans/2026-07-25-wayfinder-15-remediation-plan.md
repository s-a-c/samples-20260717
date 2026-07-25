# Wayfinder #15 Gap Remediation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development (recommended) or executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all compliance gaps identified in the Wayfinder #15 compliance report — restore ADR documentation, unify domain structure, fix testing infrastructure, configure quality tooling, and build missing features.

**Architecture:** The codebase is substantially implemented from the wayfinder decisions. This plan targets remaining gaps in priority order: (P0) ADR recovery + CI enforcement, (P1) Domain structure refactoring + arch rules, (P2–P3) quality tooling + missing features. All remediation is additive or relocational — no existing domain model or service logic is deleted.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, PHPStan 2, Rector 2, Mago 1, PostgreSQL 18/pgvector, GitHub Actions

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Global Constraints](#1-global-constraints)
  - [1.1. Task 1: Recover ADR Documentation (P0 — HIGHEST PRIORITY)](#11-task-1-recover-adr-documentation-p0--highest-priority)
  - [1.2. Task 2: Refactor Domain Structure — Move Product Stubs + Policies into `app/Domain/` (P1)](#12-task-2-refactor-domain-structure--move-product-stubs--policies-into-appdomain-p1)
  - [1.3. Task 3: CI — Add pgvector Service Container and Coverage Support](#13-task-3-ci--add-pgvector-service-container-and-coverage-support)
  - [1.4. Task 4: Add Dedicated Composer Test Scripts](#14-task-4-add-dedicated-composer-test-scripts)
  - [1.5. Task 5: Add Missing Architecture (arch) Rules](#15-task-5-add-missing-architecture-arch-rules)
  - [1.6. Task 6: Configure Rector](#16-task-6-configure-rector)
  - [1.7. Task 7: Configure Mago](#17-task-7-configure-mago)
  - [1.8. Task 8: Create Northwind Filament Resources](#18-task-8-create-northwind-filament-resources)
  - [1.9. Task 9: Create `product_portfolio_snapshots` View Migration](#19-task-9-create-product_portfolio_snapshots-view-migration)
  - [1.10. Task 10: Create Implementation-Readiness Dossier Command](#110-task-10-create-implementation-readiness-dossier-command)
  - [1.11. Task 11: Migrate EmbeddingJob from Raw HTTP to `laravel/ai` SDK](#111-task-11-migrate-embeddingjob-from-raw-http-to-laravelai-sdk)
  - [1.12. Task 12: Create Team Artefacts Migration and Model](#112-task-12-create-team-artefacts-migration-and-model)
  - [1.13. Task 13: Update CONTEXT.md — Remove SQLite Terminology](#113-task-13-update-contextmd--remove-sqlite-terminology)
  - [1.14. Task 14: Configure Mutation Testing (Infection)](#114-task-14-configure-mutation-testing-infection)
  - [1.15. Task 15: Document macOS Herd PHPStan Quirk in README](#115-task-15-document-macos-herd-phpstan-quirk-in-readme)
  - [1.16. Task 16: Add Unit Tests for Core Services](#116-task-16-add-unit-tests-for-core-services)
  - [1.17. Task 17: Reset `phpstan-baseline.neon` with Cited Shrinkable Entries](#117-task-17-reset-phpstan-baselineneon-with-cited-shrinkable-entries)
- [2. Task Dependency Graph](#2-task-dependency-graph)
- [3. Execution Options](#3-execution-options)

</details>

---

## 1. Global Constraints

- All PHP code must pass `phpstan analyse --memory-limit=512M` at `level: max`
- All `arch()` rules must be real assertions, not stubs
- All migration files use timestamped naming conventions
- All new composer scripts follow existing naming patterns
- **All product-specific code must live under `app/Domain/{Product}/`** — Models, Policies, and any future product-specific classes. Presentation layer (Filament resources) and application services (import, reset, search) stay at their current locations.
- No new dependencies beyond what's already in `composer.json`
- Follow existing code conventions: check sibling files for structure
- Run `vendor/bin/pint --format agent` after every PHP change
- Every change must have a test (Pest 4, existing patterns)

---

### 1.1. Task 1: Recover ADR Documentation (P0 — HIGHEST PRIORITY)

**Files:**
- Create: `docs/10-architecture/1015-adrs/` (directory)
- Create: `docs/10-architecture/1015-adrs/README.md` (index)
- Create: 12 new ADR files (0006–0017)
- Move: 5 existing ADRs from `docs/adr/` to `docs/10-architecture/1015-adrs/`
- Delete: `docs/adr/` (old path, after move)

**Description:** Wayfinder #15 resolved >40 architectural decisions. Only 5 of ~17 required ADRs exist, all at the wrong path (`docs/adr/` instead of `docs/10-architecture/1015-adrs/`). The 12 missing ADRs must be recovered/restated from the wayfinder issue comments and the implementation codebase. Each ADR follows the existing format: Context → Decision → Consequences → Related.

**Existing ADRs to move (path only — no content changes):**
- `docs/adr/0001-multi-product-architecture.md`
- `docs/adr/0002-uuidv7-for-all-entities.md`
- `docs/adr/0003-postgres-native-search.md`
- `docs/adr/0004-shadow-schema-import-pipeline.md`
- `docs/adr/0005-filament-panel-isolation.md`

**Missing ADRs to recover (12 total):**

- [ ] **Step 1: Create directory structure and ADR index**

Create `docs/10-architecture/1015-adrs/` directory. Create `README.md`:

```markdown
# Architectural Decision Records

This directory contains ADRs for the Samples application.
Each ADR captures a significant architectural decision, its context, and its consequences.

## Index

| ADR                                                       | Title                                     | Status   |
| --------------------------------------------------------- | ----------------------------------------- | -------- |
| [0001](0001-multi-product-architecture.md)                | Multi-Product Architecture                | Accepted |
| [0002](0002-uuidv7-for-all-entities.md)                   | UUIDv7 for All Entities                   | Accepted |
| [0003](0003-postgres-native-search.md)                    | Postgres-Native Search (Hybrid)           | Accepted |
| [0004](0004-shadow-schema-import-pipeline.md)             | Shadow-Schema Import Pipeline             | Accepted |
| [0005](0005-filament-panel-isolation.md)                  | Filament Panel Isolation                  | Accepted |
| [0006](0006-source-identity-registry.md)                  | Source Identity Registry                  | Accepted |
| [0007](0007-product-reset-semantics.md)                   | Product Reset Semantics                   | Accepted |
| [0008](0008-spatie-shield-fortify-coexistence.md)         | Spatie + Shield + Fortify Coexistence     | Accepted |
| [0009](0009-search-document-shape-and-federation.md)      | Search Document Shape and Federation      | Accepted |
| [0010](0010-embedding-profile-and-ai-sdk.md)              | Embedding Profile and AI SDK              | Accepted |
| [0011](0011-portfolio-card-architecture.md)               | Portfolio Card Architecture               | Accepted |
| [0012](0012-team-artefacts-schema.md)                     | Team Artefacts Schema                     | Accepted |
| [0013](0013-test-pyramid.md)                              | Test Pyramid                              | Accepted |
| [0014](0014-larastan-target-level-and-baseline-policy.md) | Larastan Target Level and Baseline Policy | Accepted |
| [0015](0015-implementation-readiness-dossier.md)          | Implementation-Readiness Dossier          | Accepted |
| [0016](0016-documentation-lifecycle.md)                   | Documentation Lifecycle                   | Accepted |
| [0017](0017-git-branch-pr-and-dependency-strategy.md)     | Git Branch, PR, and Dependency Strategy   | Accepted |

## ADR Lifecycle

1. **Draft** — Decision proposed, under review
2. **Accepted** — Decision approved and implemented
3. **Deprecated** — Superseded by a later ADR
4. **Superseded** — Replaced by a newer decision

## Cross-References

- Domain glossary: [`CONTEXT.md`](../CONTEXT.md)
```

- [ ] **Step 2: Move existing ADRs to new path**

```bash
mkdir -p docs/10-architecture/1015-adrs
cp docs/adr/0001-multi-product-architecture.md docs/10-architecture/1015-adrs/
cp docs/adr/0002-uuidv7-for-all-entities.md docs/10-architecture/1015-adrs/
cp docs/adr/0003-postgres-native-search.md docs/10-architecture/1015-adrs/
cp docs/adr/0004-shadow-schema-import-pipeline.md docs/10-architecture/1015-adrs/
cp docs/adr/0005-filament-panel-isolation.md docs/10-architecture/1015-adrs/
```

Update cross-reference paths in each moved ADR to point to the new location.

- [ ] **Step 3: Recover ADR 0006 — Source Identity Registry**

Source: Wayfinder #25 resolution. Context: product import idempotency requires stable UUIDs across resets. Decision: single shared `public.source_identities` table with JSONB `source_key`, `entity` discriminator, and generated `product` column. Write ADR at `docs/10-architecture/1015-adrs/0006-source-identity-registry.md` following the existing format.

- [ ] **Step 4: Recover ADR 0007 — Product Reset Semantics**

Source: Wayfinder #29 resolution. Context: safe product reset requires write-blocking during active windows and signed-token confirmation. Decision: app-layer `ResetWindow` service (no DB-level REVOKE), `BelongsToProductDomain` trait, `ResetConfirmation` protocol, `ResetEvidence` VO, recovery runbook with `remediation_hint` enum.

- [ ] **Step 5: Recover ADR 0008 — Spatie + Shield + Fortify Coexistence**

Source: Wayfinder #26 resolution. Context: three auth/RBAC systems (Spatie Permission, Filament Shield, Laravel Fortify) and Livewire starter team roles need to coexist without conflict. Decision: Spatie is sole RBAC engine; starter's enums reframed as membership position data; Fortify owns authentication; Shield runs in Admin panel only; 4 panel providers share web guard.

- [ ] **Step 6: Recover ADR 0009 — Search Document Shape and Federation**

Source: Wayfinder #31 + #34 resolutions. Context: three products, each with 15–16 searchable entities, need unified hybrid search. Decision: 4 weight-class text columns (A/B/C/D), `tsvector GENERATED ALWAYS AS ... STORED`, `embedding vector(1024)`, federated `UNION ALL` across product schemas, RRF fusion (k=60), static `SearchDeepLinkRegistry`.

- [ ] **Step 7: Recover ADR 0010 — Embedding Profile and AI SDK**

Source: Wayfinder #33 resolution. Context: vector embeddings need provider abstraction and dimension re-pin. Decision: `laravel/ai` SDK with OpenAI primary + OpenRouter fallback, `dimensions: 1024`, `embedding_profile` format `{provider}:{model}:{dimensions}`, after-commit queue dispatch, 3 retries then `embedding_state='failed'`.

- [ ] **Step 8: Recover ADR 0011 — Portfolio Card Architecture**

Source: Wayfinder #35 resolution. Context: Admin Dashboard needs per-product summary cards showing entity counts. Decision: Filament Widget in 3-column layout, reusable single class with `product` prop, Postgres view (`product_portfolio_snapshots`) for snapshot storage.

- [ ] **Step 9: Recover ADR 0012 — Team Artefacts Schema**

Source: Wayfinder #36 resolution. Context: teams need saved searches and dashboard layouts that survive across sessions. Decision: single polymorphic `team_artefacts` table with `type` enum + nullable `configuration` JSONB, UUIDv7 PKs, created_by FK (SET NULL on user delete), Team Owner authority override.

- [ ] **Step 10: Recover ADR 0013 — Test Pyramid**

Source: Wayfinder #17 resolution. Context: which test layers are mandatory, aspirational, or out of scope. Decision: 4 PR-gated layers (Unit/Feature/Architecture/Livewire), 80% line coverage floor, 15+ arch rules, Browser/Dusk out of scope for Stage 1, mutation testing aspirational.

- [ ] **Step 11: Recover ADR 0014 — Larastan Target Level and Baseline Policy**

Source: Wayfinder #18 resolution. Context: what PHPStan level and baseline policy to use. Decision: `level: max`, no baseline file, framework-idiom carve-outs only (cited by ticket), macOS Herd `--threads=1` quirk documented.

- [ ] **Step 12: Recover ADR 0015 — Implementation-Readiness Dossier**

Source: Wayfinder #37 resolution. Context: how to track implementation readiness across 8 acceptance stages. Decision: hand-reviewable Markdown under `docs/15-delivery/1515-implementation-readiness-dossier/`, `dossier:generate` Artisan command, stage files with 7-section layout, CI-generated on post-merge.

- [ ] **Step 13: Recover ADR 0016 — Documentation Lifecycle**

Source: Wayfinder #38 resolution. Context: what documentation to produce and when. Decision: full ADR set, concise README, operator runbook folded into dossier stage files, proportional incremental delivery per stage.

- [ ] **Step 14: Recover ADR 0017 — Git Branch, PR, and Dependency Strategy**

Source: Wayfinder #39 resolution. Context: how to organise branches, PRs, and dependency updates. Decision: trunk-based + short-lived feature branches off main, squash merge, Dependabot weekly grouped for composer/npm/actions, no `act`.

- [ ] **Step 15: Delete old ADR directory**

```bash
rm -r docs/adr/
```

- [ ] **Step 16: Verify all ADRs render correctly**

Run: `ls docs/10-architecture/1015-adrs/*.md | wc -l`

Expected: 18 files (17 ADRs + 1 README).

- [ ] **Step 17: Commit**

```bash
git add docs/10-architecture/1015-adrs/
git rm -r docs/adr/
git commit -m "docs: recover 12 missing ADRs from wayfinder #15 decisions; move to docs/10-architecture/1015-adrs/"
```

---

### 1.2. Task 2: Refactor Domain Structure — Move Product Stubs + Policies into `app/Domain/` (P1)

**Files:**
- Move: `app/Models/Chinook/Chinook.php` → `app/Domain/Chinook/Models/Chinook.php`
- Move: `app/Models/Northwind/Northwind.php` → `app/Domain/Northwind/Models/Northwind.php`
- Move: `app/Policies/Chinook/ChinookPolicy.php` → `app/Domain/Chinook/Policies/ChinookPolicy.php`
- Move: `app/Policies/Northwind/NorthwindPolicy.php` → `app/Domain/Northwind/Policies/NorthwindPolicy.php`
- Create: `app/Domain/Pagila/Models/Pagila.php` (new Pagila stub)
- Create: `app/Domain/Pagila/Policies/PagilaPolicy.php` (new Pagila policy)
- Modify: `config/filament-shield.php` (update policies path)
- Modify: `app/Providers/AppServiceProvider.php` (update `Gate::before` imports)
- Modify: `tests/Architecture/ProductPolicyNamespaceTest.php` (update expectations)
- Modify: `tests/Architecture/ArchitectureTest.php` (update arch rules for new paths)

**Description:** Product-specific model stubs (for Spatie permission checks) and policies currently live under `app/Models/` and `app/Policies/`. Per the `app/Domain/{Product}/` convention, they must move into the domain directory. The Pagila product is also missing its stub model and policy entirely.

- [ ] **Step 1: Read existing stub models for template**

```bash
cat app/Models/Chinook/Chinook.php && echo "===" && cat app/Models/Northwind/Northwind.php
```

Both are simple models extending `Illuminate\Database\Eloquent\Model` with `HasRoles` trait and no table binding.

- [ ] **Step 2: Create new Chinook stub at correct path**

Create `app/Domain/Chinook/Models/Chinook.php`:

```php
<?php

namespace App\Domain\Chinook\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Chinook extends Model
{
    use HasRoles;

    protected $connection = 'pgsql';
    protected $table = 'chinook.placeholder';
    public $timestamps = false;
}
```

- [ ] **Step 3: Create new Northwind stub at correct path**

Create `app/Domain/Northwind/Models/Northwind.php`:

```php
<?php

namespace App\Domain\Northwind\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Northwind extends Model
{
    use HasRoles;

    protected $connection = 'pgsql';
    protected $table = 'northwind.placeholder';
    public $timestamps = false;
}
```

- [ ] **Step 4: Create new Pagila stub**

Create `app/Domain/Pagila/Models/Pagila.php`:

```php
<?php

namespace App\Domain\Pagila\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Pagila extends Model
{
    use HasRoles;

    protected $connection = 'pgsql';
    protected $table = 'pagila.placeholder';
    public $timestamps = false;
}
```

- [ ] **Step 5: Read existing policies for template**

```bash
cat app/Policies/Chinook/ChinookPolicy.php && echo "===" && cat app/Policies/Northwind/NorthwindPolicy.php
```

- [ ] **Step 6: Create new Chinook policy at correct path**

Create `app/Domain/Chinook/Policies/ChinookPolicy.php`:

```php
<?php

namespace App\Domain\Chinook\Policies;

use App\Domain\Chinook\Models\Chinook;
use App\Models\User;

class ChinookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('chinook_curator') || $user->hasRole('super_admin');
    }

    public function view(User $user, Chinook $chinook): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Chinook $chinook): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Chinook $chinook): bool
    {
        return $this->viewAny($user);
    }
}
```

- [ ] **Step 7: Create new Northwind policy at correct path**

Create `app/Domain/Northwind/Policies/NorthwindPolicy.php` with namespace `App\Domain\Northwind\Policies`, importing `App\Domain\Northwind\Models\Northwind`. Same role pattern as Chinook but using `northwind_curator`.

- [ ] **Step 8: Create new Pagila policy**

Create `app/Domain/Pagila/Policies/PagilaPolicy.php`:

```php
<?php

namespace App\Domain\Pagila\Policies;

use App\Domain\Pagila\Models\Pagila;
use App\Models\User;

class PagilaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('pagila_curator') || $user->hasRole('super_admin');
    }

    public function view(User $user, Pagila $pagila): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Pagila $pagila): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Pagila $pagila): bool
    {
        return $this->viewAny($user);
    }
}
```

- [ ] **Step 9: Delete old files**

```bash
rm app/Models/Chinook/Chinook.php
rm app/Models/Northwind/Northwind.php
rm app/Policies/Chinook/ChinookPolicy.php
rm app/Policies/Northwind/NorthwindPolicy.php
rmdir app/Models/Chinook/ 2>/dev/null || true
rmdir app/Models/Northwind/ 2>/dev/null || true
rmdir app/Policies/Chinook/ 2>/dev/null || true
rmdir app/Policies/Northwind/ 2>/dev/null || true
```

- [ ] **Step 10: Update config/filament-shield.php policies path**

Change the `policies.path` value to point to the domain policies directory. Since policies are now distributed across `app/Domain/{Chinook,Northwind,Pagila}/Policies/`, Shield needs an additional discovery path.

Add a new policy path alongside the existing one, or extend the path:

```php
'policies' => [
    'path' => app_path('Policies'),
    // Add domain policy directories for Shield discovery
    'paths' => [
        app_path('Policies'),
        app_path('Domain/Chinook/Policies'),
        app_path('Domain/Northwind/Policies'),
        app_path('Domain/Pagila/Policies'),
    ],
    ...
],
```

Note: Verify that `config/filament-shield.php` supports a `paths` array (consult the Shield docs). If it only takes a single string, register the Domain policies directory as the sole path:

```php
'policies' => [
    'path' => app_path('Domain'),
    ...
],
```

- [ ] **Step 11: Verify policy discovery**

Run: `php artisan shield:generate --panel=admin --no-interaction 2>&1 | head -20`

Expected: Shield discovers policies from the new locations without error.

- [ ] **Step 12: Update arch tests**

Modify `tests/Architecture/ProductPolicyNamespaceTest.php` to point at `app/Domain/{Product}/Policies/` instead of `app/Policies/{Product}/`.

Modify `tests/Architecture/ArchitectureTest.php` to add an arch rule enforcing the convention:

```php
arch('Product stubs must live under app/Domain/{Product}/Models/')
    ->expect('App\Models')
    ->not->toUse('App\Models\Chinook')
    ->not->toUse('App\Models\Northwind');
```

- [ ] **Step 13: Run tests**

Run: `php artisan test tests/Architecture --compact 2>&1 | tail -15`

Expected: ALL arch tests pass.

- [ ] **Step 14: Run PHPStan**

Run: `XDEBUG_MODE=off php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=512M --no-progress 2>&1 | tail -10`

Expected: `[OK] No errors`.

- [ ] **Step 15: Commit**

```bash
git add app/Domain/Chinook/Models/Chinook.php app/Domain/Chinook/Policies/
git add app/Domain/Northwind/Models/Northwind.php app/Domain/Northwind/Policies/
git add app/Domain/Pagila/Models/Pagila.php app/Domain/Pagila/Policies/
git add config/filament-shield.php tests/Architecture/
git rm app/Models/Chinook/Chinook.php app/Models/Northwind/Northwind.php
git rm app/Policies/Chinook/ChinookPolicy.php app/Policies/Northwind/NorthwindPolicy.php
git commit -m "refactor: move product stubs and policies into app/Domain/; add missing Pagila stub/policy"
```

---

### 1.3. Task 3: CI — Add pgvector Service Container and Coverage Support

> Same as original Task 1 — unchanged. See the prior plan draft for full details.

**Files:**
- Modify: `.github/workflows/tests.yml`
- Modify: `phpunit.xml`

**Description:** CI lacks pgvector service container and coverage enforcement.

- [ ] **Step 1: Add pgvector/pgvector:pg18 service container**

Add `services:` block with health check, using `DB_DATABASE=testing`, `DB_USERNAME=s-a-c`, `DB_PASSWORD=secret`.

- [ ] **Step 2: Enable pcov coverage in setup-php step** (`coverage: pcov`)

- [ ] **Step 3: Add DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD env vars to CI check step**

- [ ] **Step 4: Add `<coverage>` element to phpunit.xml**

- [ ] **Step 5: Add `--coverage --min=80` to `test` composer script**

- [ ] **Step 6: Run `composer ci:check` locally to verify no regression**

- [ ] **Step 7: Commit**

```bash
git add .github/workflows/tests.yml phpunit.xml composer.json
git commit -m "ci: add pgvector service, enable coverage, enforce 80% min coverage"
```

---

### 1.4. Task 4: Add Dedicated Composer Test Scripts

> Same as original Task 2.

**Files:**
- Modify: `composer.json`

**Description:** Wayfinder #17 specifies dedicated test scripts.

- [ ] **Step 1: Add `test:unit`, `test:feature`, `test:pg`, `test:arch`, `test:coverage`, `test:type-cov`, `test:livewire` composer scripts**

- [ ] **Step 2: Add type coverage threshold configuration** (`tests/TypeCoverage.php`)

- [ ] **Step 3: Verify scripts callable** (`php artisan test --type-coverage --compact`)

- [ ] **Step 4: Commit**

---

### 1.5. Task 5: Add Missing Architecture (arch) Rules

> Updated from original Task 3 — now also enforces `app/Domain/` convention.

**Files:**
- Modify: `tests/Architecture/ArchitectureTest.php`

**Description:** Wayfinder #17 specifies 17+ arch rules. Currently 10 exist. Add the 7 missing rules.

- [ ] **Step 1: Add Domain structure convention rule**

```php
arch('Product model stubs must live in App\Domain\* namespace')
    ->expect('App\Models')
    ->not->toUse('Spatie\Permission\Traits\HasRoles');
```

This asserts that Spatie HasRoles is only used by domain-namespaced models, not by anything in `App\Models`.

- [ ] **Step 2: Add Reset service ownership rules** (4 rules from #29)

  - Only `ResetWindow` may query `reset_runs` for write-blocking state
  - Only `ResetConfirmationService` may write to `reset_confirmations`
  - Only `RecoveryService` may transition `recovering → running/succeeded`
  - Product importers must not reference sibling product namespaces

- [ ] **Step 3: Add presentation-layer isolation rules** (3 rules from #17)

  - No `DB::` facade from `App\Http` or `App\Filament`
  - No `app()`/`resolve()` from `App\Http` or `App\Filament`

- [ ] **Step 4: Run arch tests**

Run: `php artisan test tests/Architecture --compact 2>&1 | tail -20`

Expected: ALL pass (if a rule fails, refine with `->ignoring()` for legitimate uses).

- [ ] **Step 5: Commit**

---

### 1.6. Task 6: Configure Rector

> Same as original Task 4 — unchanged.

**Files:**
- Create: `rector.php`
- Modify: `composer.json`

- [ ] **Step 1: Create `rector.php`** with paths (`app/`, `config/`, `database/`, `routes/`, `tests/`), Laravel sets, type coverage, skip `AddVoidReturnTypeWhereNoReturnRector`

- [ ] **Step 2: Add `rector` and `rector:fix` composer scripts**

- [ ] **Step 3: Run `vendor/bin/rector process --dry-run` to verify**

- [ ] **Step 4: Commit**

---

### 1.7. Task 7: Configure Mago

> Same as original Task 5 — unchanged.

**Files:**
- Create: `mago.json`
- Modify: `composer.json`

- [ ] **Step 1: Create `mago.json`** with paths, complexity limits, forbidden functions

- [ ] **Step 2: Add `mago` and `mago:fix` composer scripts**

- [ ] **Step 3: Run `vendor/bin/mago analyse` to verify**

- [ ] **Step 4: Commit**

---

### 1.8. Task 8: Create Northwind Filament Resources

> Same as original Task 7 — but also verify PanelProvider correctly discovers them.

**Files:**
- Create: `app/Filament/Northwind/Resources/` (7+ resources via `make:filament-resource`)
- Modify: `tests/Architecture/ArchitectureTest.php` (add Northwind resource arch rule)

- [ ] **Step 1: Generate Northwind resources via `make:filament-resource`** for Category, Customer, Employee, Order, Product, Shipper, Supplier — all with `--panel=northwind` and `--model-namespace="App\\Domain\\Northwind\\Models"`

- [ ] **Step 2: Add Northwind resource arch rule** to `ArchitectureTest.php`

- [ ] **Step 3: Create `tests/Feature/Filament/NorthwindResourcesTest.php`**

- [ ] **Step 4: Run tests** (`php artisan test tests/Feature/Filament/NorthwindResourcesTest.php --compact`)

- [ ] **Step 5: Commit**

---

### 1.9. Task 9: Create `product_portfolio_snapshots` View Migration

> Same as original Task 8 — unchanged.

**Files:**
- Create: `database/migrations/2026_07_24_204000_create_product_portfolio_snapshots_view.php`
- Modify: `app/Filament/Admin/Widgets/ProductPortfolioCard.php`

- [ ] **Step 1: Create migration** with `CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS ...` — one query per product using meaningful aggregate counts

- [ ] **Step 2: Update `ProductPortfolioCard`** to query the view rather than raw DB

- [ ] **Step 3: Verify migration** (`php artisan migrate --pretend`)

- [ ] **Step 4: Commit**

---

### 1.10. Task 10: Create Implementation-Readiness Dossier Command

> Same as original Task 9 — unchanged.

**Files:**
- Create: `app/Console/Commands/DossierGenerate.php`
- Create: `docs/15-delivery/1515-implementation-readiness-dossier/151501-contents.md`

- [ ] **Step 1: Create `DossierGenerate` command** with signature `dossier:generate {--path=}`

- [ ] **Step 2: Create initial dossier contents file** at `docs/15-delivery/1515-implementation-readiness-dossier/151501-contents.md`

- [ ] **Step 3: Verify command** (`php artisan dossier:generate`)

- [ ] **Step 4: Commit**

---

### 1.11. Task 11: Migrate EmbeddingJob from Raw HTTP to `laravel/ai` SDK

> Same as original Task 10 — unchanged.

**Files:**
- Modify: `app/Jobs/EmbeddingJob.php`

- [ ] **Step 1: Replace `Http::withToken(...)->post(...)` with `AI::embeddings()->generate($text)`**

- [ ] **Step 2: Update `EmbeddingJobTest`** to mock the AI SDK instead of HTTP

- [ ] **Step 3: Run search tests** (`php artisan test tests/Feature/Search --compact`)

- [ ] **Step 4: Commit**

---

### 1.12. Task 12: Create Team Artefacts Migration and Model

> Same as original Task 11 — unchanged.

**Files:**
- Create: `database/migrations/2026_07_24_203000_create_team_artefacts_table.php`
- Create: `app/Models/TeamArtefact.php`

- [ ] **Step 1: Create migration** with UUID PK, team_id FK, created_by FK (SET NULL), type string, configuration JSONB, soft deletes, timestamps

- [ ] **Step 2: Create `TeamArtefact` Eloquent model** with HasUuids, SoftDeletes, casts for configuration+last_run_at, team/creator relationships

- [ ] **Step 3: Verify migration** (`php artisan migrate --pretend`)

- [ ] **Step 4: Commit**

---

### 1.13. Task 13: Update CONTEXT.md — Remove SQLite Terminology

> Same as original Task 12 — unchanged.

**Files:**
- Modify: `CONTEXT.md`

- [ ] **Step 1: Identify all SQLite-specific terms** (`grep -n -i 'sqlite\|fts5\|vec0\|sqlite-vec' CONTEXT.md`)

- [ ] **Step 2: Replace each SQLite term with Postgres equivalent** — sqlite-vec→pgvector, FTS5→tsvector+GIN, vec0→HNSW, etc.

- [ ] **Step 3: Verify no SQLite terms remain** (`grep -i 'sqlite\|fts5\|vec0' CONTEXT.md`)

- [ ] **Step 4: Commit**

---

### 1.14. Task 14: Configure Mutation Testing (Infection)

> Same as original Task 13 — unchanged.

**Files:**
- Create: `infection.json.dist`
- Modify: `composer.json`

- [ ] **Step 1: Create `infection.json.dist`** with source dirs, default mutators, min MSI 50/min covered MSI 60

- [ ] **Step 2: Add `test:mutation` composer script**

- [ ] **Step 3: Verify infection available** (`composer show infection/infection`; install if missing)

- [ ] **Step 4: Commit**

---

### 1.15. Task 15: Document macOS Herd PHPStan Quirk in README

> Same as original Task 14 — unchanged.

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Add "Verification Scripts" section** documenting the macOS Herd `--threads=1` PHPStan quirk, all new test scripts, and quality tool commands (rector, mago, infection)

- [ ] **Step 2: Commit**

---

### 1.16. Task 16: Add Unit Tests for Core Services

> Same as original Task 15 — unchanged.

**Files:**
- Create: `tests/Unit/ReciprocalRankFusionTest.php`
- Create: `tests/Unit/ResetEvidenceTest.php`

- [ ] **Step 1: Create RRF unit test** — fuses result sets, handles empty inputs

- [ ] **Step 2: Create ResetEvidence unit test** — schema version, invalid key rejection, valid config

- [ ] **Step 3: Run unit tests** (`composer test:unit`)

- [ ] **Step 4: Commit**

---

### 1.17. Task 17: Reset `phpstan-baseline.neon` with Cited Shrinkable Entries

> Same as original Task 16 — unchanged.

**Files:**
- Modify: `phpstan-baseline.neon`

- [ ] **Step 1: Categorise baseline entries** by source (Spatie, Filament deprecated, domain type issues)

- [ ] **Step 2: Add retiring-ticket citations** to each entry (`# bd: <ticket>` or `# framework-idiom`)

- [ ] **Step 3: Verify PHPStan passes** (`XDEBUG_MODE=off php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=512M --no-progress`)

- [ ] **Step 4: Commit**

---

## 2. Task Dependency Graph

```
              ┌─────────────────────────────────────┐
              │ Task 1: Recover ADR docs (P0)        │ ← DO FIRST
              │ (17 ADRs, directory restructure)      │
              └─────────────────────────────────────┘
                           │
              ┌─────────────────────────────────────┐
              │ Task 2: Refactor Domain structure    │ ← MUST come before
              │ (Move stubs + policies to app/Domain/)│   arch rules (Task 5)
              └─────────────────────────────────────┘
                           │
     ┌─────────────────────┼─────────────────────┐
     │                     │                      │
     ▼                     ▼                      ▼
  Task 3 (CI)          Task 4 (Scripts)      Task 5 (Arch rules)
  Task 6 (Rector)      Task 7 (Mago)         Task 8 (Northwind resources)
  Task 9 (Portfolio)   Task 10 (Dossier)     Task 11 (EmbeddingJob SDK)
  Task 12 (Artefacts)  Task 13 (CONTEXT.md)  Task 14 (Infection)
  Task 15 (README)     Task 16 (Unit tests)  Task 17 (PHPStan baseline)

  ALL independent after Tasks 1–2 — can run in parallel batches
```

**Critical ordering:**
1. **Task 1 first** — ADRs are the highest priority gap; all other decisions should reference them
2. **Task 2 second** — Domain refactoring changes file paths that arch rules (Task 5) and tests must match
3. **Tasks 3–17** — All independent of each other once Tasks 1–2 are done

---

## 3. Execution Options

**Plan complete. Two execution options:**

1. **Subagent-Driven (recommended)** — Dispatch a fresh subagent per task, review between tasks, fast iteration. Use subagent-driven-development skill. Tasks 1–2 run first; Tasks 3–17 parallelise after.

2. **Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints.

**Which approach?**
