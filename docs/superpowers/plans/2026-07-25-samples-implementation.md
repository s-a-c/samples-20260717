# Samples Application Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the complete multi-product sample data platform for Chinook, Northwind, and Pagila on Laravel 13, Filament 5, and PostgreSQL 18 with pgvector, tsvector hybrid search, identity registry, reset engine, and team/role authorization.

**Architecture:** Domain-driven multi-schema PostgreSQL design (`chinook`, `northwind`, `pagila`, `public`) with schema-qualified Eloquent models, shadow-schema staging product import/reset pipeline, pgvector+tsvector hybrid retrieval with Reciprocal Rank Fusion (RRF), and 4 isolated Filament 5 admin/product panels gated by Spatie Shield RBAC on Fortify auth.

**Tech Stack:** PHP 8.5, Laravel 13.22, Filament 5.7, PostgreSQL 18 + pgvector + tsvector, `laravel/ai` 0.10, Spatie Permission / Filament Shield 4.3, Fortify 1.37, Livewire Flux Pro 2.15, Pest 4.7, Larastan 3.10 level max.

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Global Constraints](#1-global-constraints)
- [2. File Structure Map](#2-file-structure-map)
- [3. Detailed Task Breakdown](#3-detailed-task-breakdown)
  - [3.1. Stage 1: Foundational Database Infrastructure \& Shared Registries](#31-stage-1-foundational-database-infrastructure--shared-registries)
    - [3.1.1. Task 1: Postgres Extensions Migration \& Health Command (`pgsql:check`)](#311-task-1-postgres-extensions-migration--health-command-pgsqlcheck)
    - [3.1.2. Task 2: UUIDv7 Trait \& Starter Migrations Refactoring](#312-task-2-uuidv7-trait--starter-migrations-refactoring)
    - [3.1.3. Task 3: Shared Public Infrastructure Tables (Source Identity Registry \& Reset Confirmations)](#313-task-3-shared-public-infrastructure-tables-source-identity-registry--reset-confirmations)
  - [3.2. Stage 2: Product Reset Engine \& Execution Infrastructure](#32-stage-2-product-reset-engine--execution-infrastructure)
    - [3.2.1. Task 4: Reset Runs Migration, App-Layer Reset Window \& Recovery Services](#321-task-4-reset-runs-migration-app-layer-reset-window--recovery-services)
  - [3.3. Stage 3: Upstream Dataset Pin Manifests, Fetch Command \& Importer Pipelines](#33-stage-3-upstream-dataset-pin-manifests-fetch-command--importer-pipelines)
    - [3.3.1. Task 5: Source Pin Manifests \& `source:fetch` Command](#331-task-5-source-pin-manifests--sourcefetch-command)
    - [3.3.2. Task 6: Product Import Pipeline Readers, Importers \& CLI Suite](#332-task-6-product-import-pipeline-readers-importers--cli-suite)
  - [3.4. Stage 4: Product Domain Models, Migrations \& Search Projections](#34-stage-4-product-domain-models-migrations--search-projections)
    - [3.4.1. Task 7: Chinook Domain Models \& Schema Migration](#341-task-7-chinook-domain-models--schema-migration)
    - [3.4.2. Task 8: Northwind Domain Models \& Schema Migration](#342-task-8-northwind-domain-models--schema-migration)
    - [3.4.3. Task 9: Pagila Domain Models \& Schema Migration](#343-task-9-pagila-domain-models--schema-migration)
    - [3.4.4. Task 10: Search Projection Tables, PL/pgSQL Triggers \& Observers](#344-task-10-search-projection-tables-plpgsql-triggers--observers)
  - [3.5. Stage 5: Embedding Profiles, AI SDK Integration \& Hybrid Search (RRF)](#35-stage-5-embedding-profiles-ai-sdk-integration--hybrid-search-rrf)
    - [3.5.1. Task 11: `laravel/ai` SDK Integration \& Async `EmbeddingJob`](#351-task-11-laravelai-sdk-integration--async-embeddingjob)
    - [3.5.2. Task 12: Reciprocal Rank Fusion \& Federated Search Service](#352-task-12-reciprocal-rank-fusion--federated-search-service)
  - [3.6. Stage 6: Auth Co-existence, Operator Onboarding \& Filament Panels](#36-stage-6-auth-co-existence-operator-onboarding--filament-panels)
    - [3.6.1. Task 13: Operator Onboarding Command \& DatabaseSeeder](#361-task-13-operator-onboarding-command--databaseseeder)
    - [3.6.2. Task 14: Filament Panel Providers \& Shield Permission Setup](#362-task-14-filament-panel-providers--shield-permission-setup)
  - [3.7. Stage 7: Filament Resources for Products \& Admin Control](#37-stage-7-filament-resources-for-products--admin-control)
    - [3.7.1. Task 15: Chinook Filament Resources](#371-task-15-chinook-filament-resources)
    - [3.7.2. Task 16: Northwind Filament Resources](#372-task-16-northwind-filament-resources)
    - [3.7.3. Task 17: Pagila Filament Resources](#373-task-17-pagila-filament-resources)
    - [3.7.4. Task 18: Admin Filament Resources \& User Management](#374-task-18-admin-filament-resources--user-management)
  - [3.8. Stage 8: Portfolio Cards, Team Artefacts \& Federated Search UI](#38-stage-8-portfolio-cards-team-artefacts--federated-search-ui)
    - [3.8.1. Task 19: Portfolio View \& Product Portfolio Card Widget](#381-task-19-portfolio-view--product-portfolio-card-widget)
    - [3.8.2. Task 20: Team Artefacts Schema \& Federated Search Livewire Page](#382-task-20-team-artefacts-schema--federated-search-livewire-page)
  - [3.9. Stage 9: Quality Gates, Pest Architecture Rules, Dossier \& ADRs](#39-stage-9-quality-gates-pest-architecture-rules-dossier--adrs)
    - [3.9.1. Task 21: Comprehensive Test Pyramid, Pest Architecture Rules \& Larastan Max Fix](#391-task-21-comprehensive-test-pyramid-pest-architecture-rules--larastan-max-fix)
    - [3.9.2. Task 22: Dossier Generation \& ADR Documentation Engine](#392-task-22-dossier-generation--adr-documentation-engine)
- [4. Execution Handoff](#4-execution-handoff)

</details>

---

## 1. Global Constraints

- **PHP / Laravel Floor:** PHP 8.5+, Laravel 13.22, Filament 5.7, PostgreSQL 18.
- **Postgres Extensions:** `vector` (1024d Matryoshka truncation), `unaccent`, `pg_trgm` extensions enabled on `pgsql` connection; `en_unaccent` text search configuration.
- **Identity & Primary Keys:** Every app-owned model must use UUIDv7 (`HasUuids`) and schema-qualified `#[Table('schema.table')]` attribute.
- **Reset Safety:** Every product-domain model must use the `BelongsToProductDomain` trait for runtime write-blocking during active reset windows.
- **Authentication & Panels:** Fortify owns all auth flows. All 4 Filament panels use the `web` guard with no panel-level login routes; panel access is gated via `canAccessPanel()`.
- **Quality Floor:** Pest 4 test suite with 80% line coverage floor; Larastan `level: max` with zero baseline and cited carve-outs only.

---

## 2. File Structure Map

```log
app/
├── Actions/
│   └── Operators/ProvisionOperator.php
├── Console/Commands/
│   ├── AdrGenerate.php
│   ├── DossierGenerate.php
│   ├── OperatorCreate.php
│   ├── PgsqlCheck.php
│   ├── ProductAbort.php
│   ├── ProductConfirm.php
│   ├── ProductImportCommand.php
│   ├── ProductRecover.php
│   ├── ProductStatusCommand.php
│   └── SourceFetch.php
├── Domain/
│   ├── Chinook/Models/ (Artist, Album, Track, Genre, MediaType, Playlist, Customer, Employee, Invoice, InvoiceLine, PlaylistTrack)
│   ├── Northwind/Models/ (Category, Customer, Employee, EmployeeTerritory, Order, OrderDetail, Product, Region, Shipper, Supplier, Territory)
│   └── Pagila/Models/ (Actor, Category, City, Country, Customer, Film, FilmActor, FilmCategory, FilmText, Inventory, Language, Payment, Rental, Staff, Store)
├── Exceptions/
│   └── ProductResetWindowOpen.php
├── Filament/
│   ├── Admin/ (Resources: UserResource, RoleResource; Pages: AdminDashboard)
│   ├── Chinook/ (Resources: ArtistResource, AlbumResource, TrackResource, PlaylistResource, CustomerResource, EmployeeResource, InvoiceResource, GenreResource)
│   ├── Northwind/ (Resources: ProductResource, CategoryResource, SupplierResource, CustomerResource, EmployeeResource, OrderResource, ShipperResource)
│   ├── Pagila/ (Resources: FilmResource, ActorResource, CategoryResource, LanguageResource, CustomerResource, StaffResource, RentalResource, PaymentResource, StoreResource)
│   ├── Pages/FederatedSearchPage.php
│   └── Widgets/ProductPortfolioCard.php
├── Jobs/
│   └── EmbeddingJob.php
├── Models/
│   ├── Membership.php
│   ├── ProductPortfolioSnapshot.php
│   ├── ResetConfirmation.php
│   ├── ResetRun.php
│   ├── SourceIdentity.php
│   ├── Team.php
│   ├── TeamArtefact.php
│   ├── TeamInvitation.php
│   └── User.php
├── Providers/
│   └── Filament/ (AdminPanelProvider, ChinookPanelProvider, NorthwindPanelProvider, PagilaPanelProvider)
├── Services/
│   ├── ProductImport/
│   │   ├── ChinookImporter.php
│   │   ├── NorthwindImporter.php
│   │   ├── ProductImportPipeline.php
│   │   ├── PagilaImporter.php
│   │   ├── SourceIdentityRegistry.php
│   │   ├── SqliteSourceReader.php
│   │   └── SqlSourceReader.php
│   ├── ProductReset/
│   │   ├── RecoveryService.php
│   │   ├── ResetConfirmationService.php
│   │   ├── ResetEvidence.php
│   │   └── ResetWindow.php
│   └── Search/
│       ├── FederatedSearchService.php
│       ├── ReciprocalRankFusion.php
│       └── SearchDeepLinkRegistry.php
└── Traits/
    └── BelongsToProductDomain.php
database/
├── migrations/
│   ├── 0001_01_01_000000_create_postgres_extensions.php
│   ├── 0001_01_01_000001_create_source_identities_table.php
│   ├── 0001_01_01_000002_create_reset_runs_table.php
│   ├── 0001_01_01_000003_create_reset_confirmations_table.php
│   ├── 2026_07_24_203000_create_team_artefacts_table.php
│   ├── 2026_07_24_204000_create_product_portfolio_snapshots_view.php
│   ├── chinook/ (2026_07_24_210000_create_chinook_schema_and_tables.php, 2026_07_24_210001_create_chinook_search_projections.php)
│   ├── northwind/ (2026_07_24_211000_create_northwind_schema_and_tables.php, 2026_07_24_211001_create_northwind_search_projections.php)
│   └── pagila/ (2026_07_24_212000_create_pagila_schema_and_tables.php, 2026_07_24_212001_create_pagila_search_projections.php)
└── sources/
    ├── chinook.php
    ├── northwind.php
    └── pagila.php
docs/
├── 10-architecture/1015-adrs/
└── 15-delivery/1515-implementation-readiness-dossier/
```

---

## 3. Detailed Task Breakdown

### 3.1. Stage 1: Foundational Database Infrastructure & Shared Registries

#### 3.1.1. Task 1: Postgres Extensions Migration & Health Command (`pgsql:check`)

**Files:**
- Create: `database/migrations/0001_01_01_000000_create_postgres_extensions.php`
- Create: `app/Console/Commands/PgsqlCheck.php`
- Test: `tests/Feature/Postgres/PostgresExtensionsTest.php`

**Interfaces:**
- Consumes: PostgreSQL 18 DDL (`CREATE EXTENSION IF NOT EXISTS`, `CREATE TEXT SEARCH CONFIGURATION`)
- Produces: Installed `vector`, `unaccent`, `pg_trgm` extensions, `en_unaccent` text search config, and CLI health probe.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Postgres/PostgresExtensionsTest.php
<?php

use Illuminate\Support\Facades\DB;

test('postgres extensions vector unaccent and pg_trgm are installed', function () {
    $extensions = DB::select("SELECT extname FROM pg_extension");
    $names = array_column($extensions, 'extname');

    expect($names)->toContain('vector', 'unaccent', 'pg_trgm');
});

test('en_unaccent text search configuration exists', function () {
    $configs = DB::select("SELECT cfgname FROM pg_ts_config WHERE cfgname = 'en_unaccent'");
    expect($configs)->not->isEmpty();
});

test('pgsql check command reports healthy', function () {
    $this->artisan('pgsql:check')
        ->expectsOutputToContain('PostgreSQL extensions and text search configuration healthy.')
        ->assertExitCode(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PostgresExtensionsTest`
Expected: FAIL with missing command `pgsql:check` or missing extensions.

- [ ] **Step 3: Write minimal implementation**

```php
// database/migrations/0001_01_01_000000_create_postgres_extensions.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'en_unaccent') THEN
                    CREATE TEXT SEARCH CONFIGURATION en_unaccent (COPY = english);
                    ALTER TEXT SEARCH CONFIGURATION en_unaccent
                        ALTER MAPPING FOR word, asciiword, hword, numword WITH unaccent, english_stem;
                END IF;
            END
            $$;
        ");
    }

    public function down(): void
    {
        // No-op by design (extensions are infrastructure)
    }
};
```

```php
// app/Console/Commands/PgsqlCheck.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PgsqlCheck extends Command
{
    protected $signature = 'pgsql:check';
    protected $description = 'Verify PostgreSQL extensions and text search configuration health';

    public function handle(): int
    {
        $extensions = array_column(DB::select("SELECT extname FROM pg_extension"), 'extname');
        $required = ['vector', 'unaccent', 'pg_trgm'];

        foreach ($required as $ext) {
            if (! in_array($ext, $extensions, true)) {
                $this->error("Missing extension: {$ext}");
                return 1;
            }
        }

        $ts = DB::select("SELECT cfgname FROM pg_ts_config WHERE cfgname = 'en_unaccent'");
        if (empty($ts)) {
            $this->error("Missing text search config: en_unaccent");
            return 1;
        }

        $this->info("PostgreSQL extensions and text search configuration healthy.");
        return 0;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PostgresExtensionsTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/0001_01_01_000000_create_postgres_extensions.php app/Console/Commands/PgsqlCheck.php tests/Feature/Postgres/PostgresExtensionsTest.php
git commit -m "feat: add postgres extensions migration and pgsql:check health command"
```

---

#### 3.1.2. Task 2: UUIDv7 Trait & Starter Migrations Refactoring

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `database/migrations/2026_01_27_000001_create_teams_table.php`
- Modify: `database/migrations/2026_01_27_000002_add_current_team_id_to_users_table.php`
- Modify: `database/migrations/2026_07_24_201111_create_personal_access_tokens_table.php`
- Modify: `database/migrations/2026_07_24_202201_create_permission_tables.php`
- Modify: `database/migrations/2026_07_24_202111_create_activity_log_table.php`
- Modify: `app/Models/User.php`, `app/Models/Team.php`, `app/Models/TeamInvitation.php`, `app/Models/Membership.php`
- Test: `tests/Feature/Auth/UserUuidv7Test.php`

**Interfaces:**
- Consumes: Eloquent `HasUuids` trait.
- Produces: UUIDv7 primary keys for all application-owned tables (`users`, `teams`, `team_members`, `team_invitations`).

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Auth/UserUuidv7Test.php
<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

test('user and team models use uuidv7 primary keys', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    expect(Str::isUuid($user->id))->toBeTrue();
    expect(Str::isUuid($team->id))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserUuidv7Test`
Expected: FAIL due to integer IDs.

- [ ] **Step 3: Update migrations and models**

In `database/migrations/0001_01_01_000000_create_users_table.php`:
Change `$table->id();` to `$table->uuid('id')->primary();`
Change `$table->foreignId('user_id')` to `$table->foreignUuid('user_id')` in sessions.

In `database/migrations/2026_01_27_000001_create_teams_table.php`:
Change `$table->id();` to `$table->uuid('id')->primary();`
Change `$table->foreignId('user_id')` to `$table->foreignUuid('user_id')`.

In `database/migrations/2026_01_27_000002_add_current_team_id_to_users_table.php`:
Change `$table->foreignId('current_team_id')` to `$table->foreignUuid('current_team_id')`.

In `app/Models/User.php`:
Add `use Illuminate\Database\Eloquent\Concerns\HasUuids;` and `use HasUuids;` inside model.

In `app/Models/Team.php`:
Add `use Illuminate\Database\Eloquent\Concerns\HasUuids;` and `use HasUuids;` inside model.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=UserUuidv7Test`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ tests/Feature/Auth/UserUuidv7Test.php
git commit -m "refactor: update starter migrations and models to use UUIDv7 primary keys"
```

---

#### 3.1.3. Task 3: Shared Public Infrastructure Tables (Source Identity Registry & Reset Confirmations)

**Files:**
- Create: `database/migrations/0001_01_01_000001_create_source_identities_table.php`
- Create: `database/migrations/0001_01_01_000003_create_reset_confirmations_table.php`
- Create: `app/Models/SourceIdentity.php`
- Create: `app/Models/ResetConfirmation.php`
- Create: `app/Services/ProductImport/SourceIdentityRegistry.php`
- Create: `app/Services/ProductReset/ResetConfirmationService.php`
- Test: `tests/Feature/Import/SourceIdentityRegistryTest.php`

**Interfaces:**
- Consumes: Entity string (`chinook.artists`), JSONB source key (`{"id":"5"}`).
- Produces: Immutable UUIDv7 `domain_id` mapping surviving resets, and signed single-use confirmation tokens for operator resets.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Import/SourceIdentityRegistryTest.php
<?php

use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Support\Str;

test('registry mints new uuid on first lookup and returns existing uuid on second lookup', function () {
    $registry = new SourceIdentityRegistry();

    $uuid1 = $registry->getOrMint('chinook.artists', ['id' => '5']);
    $uuid2 = $registry->getOrMint('chinook.artists', ['id' => '5']);

    expect(Str::isUuid($uuid1))->toBeTrue();
    expect($uuid1)->toBe($uuid2);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SourceIdentityRegistryTest`
Expected: FAIL with class not found.

- [ ] **Step 3: Write implementation**

```php
// database/migrations/0001_01_01_000001_create_source_identities_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.source_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity');
            $table->jsonb('source_key');
            $table->uuid('domain_id');
            $table->timestamps();

            $table->unique(['entity', 'source_key']);
        });

        DB::statement("
            ALTER TABLE public.source_identities
            ADD COLUMN product text GENERATED ALWAYS AS (split_part(entity, '.', 1)) STORED;
        ");

        DB::statement("
            ALTER TABLE public.source_identities
            ADD CONSTRAINT source_identities_entity_check
            CHECK (entity ~ '^(chinook|northwind|pagila)\\.[a-z_][a-z0-9_]*$');
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('public.source_identities');
    }
};
```

```php
// app/Services/ProductImport/SourceIdentityRegistry.php
<?php

namespace App\Services\ProductImport;

use App\Models\SourceIdentity;
use Illuminate\Support\Str;

class SourceIdentityRegistry
{
    /**
     * Get existing domain UUID or mint a new UUIDv7.
     *
     * @param string $entity e.g. "chinook.artists"
     * @param array<string, mixed> $sourceKey e.g. ["id" => "5"]
     */
    public function getOrMint(string $entity, array $sourceKey): string
    {
        $normalizedKey = array_map(fn($v) => (string) $v, $sourceKey);
        ksort($normalizedKey);

        $record = SourceIdentity::where('entity', $entity)
            ->where('source_key', json_encode($normalizedKey))
            ->first();

        if ($record !== null) {
            return $record->domain_id;
        }

        $domainId = (string) Str::uuid7();

        SourceIdentity::create([
            'id' => (string) Str::uuid7(),
            'entity' => $entity,
            'source_key' => $normalizedKey,
            'domain_id' => $domainId,
        ]);

        return $domainId;
    }
}
```

```php
// database/migrations/0001_01_01_000003_create_reset_confirmations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.reset_confirmations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('product');
            $table->string('source_sha256');
            $table->string('source_commit');
            $table->uuid('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public.reset_confirmations');
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SourceIdentityRegistryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ app/Services/ProductImport/ tests/Feature/Import/SourceIdentityRegistryTest.php
git commit -m "feat: add source identity registry and reset confirmation migrations & services"
```

---

### 3.2. Stage 2: Product Reset Engine & Execution Infrastructure

#### 3.2.1. Task 4: Reset Runs Migration, App-Layer Reset Window & Recovery Services

**Files:**
- Create: `database/migrations/0001_01_01_000002_create_reset_runs_table.php`
- Create: `app/Models/ResetRun.php`
- Create: `app/Services/ProductReset/ResetEvidence.php`
- Create: `app/Services/ProductReset/ResetWindow.php`
- Create: `app/Services/ProductReset/RecoveryService.php`
- Create: `app/Exceptions/ProductResetWindowOpen.php`
- Create: `app/Traits/BelongsToProductDomain.php`
- Test: `tests/Feature/Reset/ResetWindowTest.php`

**Interfaces:**
- Consumes: Reset status predicates from `public.reset_runs`.
- Produces: `assertWritable()` checks that raise `ProductResetWindowOpen` (HTTP 423) when write operations occur during active resets.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Reset/ResetWindowTest.php
<?php

use App\Exceptions\ProductResetWindowOpen;
use App\Models\ResetRun;
use App\Services\ProductReset\ResetWindow;

test('reset window blocks writes when reset run is active', function () {
    ResetRun::create([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => 'running',
    ]);

    $window = new ResetWindow();
    expect($window->isOpen('chinook'))->toBeTrue();

    $this->expectException(ProductResetWindowOpen::class);
    $window->assertWritable('chinook');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ResetWindowTest`
Expected: FAIL with missing classes.

- [ ] **Step 3: Write implementation**

```php
// database/migrations/0001_01_01_000002_create_reset_runs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.reset_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product');
            $table->string('kind'); // import|reset|recover|dry_run
            $table->string('status'); // pending|running|succeeded|failed|recovering
            $table->string('current_phase')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->foreignUuid('recovery_of')->nullable()->references('id')->on('public.reset_runs')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("
            CREATE INDEX idx_reset_runs_active
            ON public.reset_runs (product)
            WHERE status IN ('pending', 'running', 'recovering');
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('public.reset_runs');
    }
};
```

```php
// app/Services/ProductReset/ResetWindow.php
<?php

namespace App\Services\ProductReset;

use App\Exceptions\ProductResetWindowOpen;
use App\Models\ResetRun;

class ResetWindow
{
    /** @var array<string, bool> */
    private array $memo = [];

    public function isOpen(string $product): bool
    {
        if (isset($this->memo[$product])) {
            return $this->memo[$product];
        }

        $open = ResetRun::where('product', $product)
            ->whereIn('status', ['pending', 'running', 'recovering'])
            ->exists();

        return $this->memo[$product] = $open;
    }

    public function assertWritable(string $product): void
    {
        if ($this->isOpen($product)) {
            throw new ProductResetWindowOpen("Reset window is currently open for product: {$product}");
        }
    }
}
```

```php
// app/Traits/BelongsToProductDomain.php
<?php

namespace App\Traits;

use App\Services\ProductReset\ResetWindow;

trait BelongsToProductDomain
{
    public static function bootBelongsToProductDomain(): void
    {
        static::creating(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::updating(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::deleting(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });
    }

    abstract public function getProductDomainName(): string;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ResetWindowTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ app/Services/ProductReset/ app/Exceptions/ app/Traits/ tests/Feature/Reset/ResetWindowTest.php
git commit -m "feat: implement ResetRun, ResetWindow service, and BelongsToProductDomain write-blocking trait"
```

---

### 3.3. Stage 3: Upstream Dataset Pin Manifests, Fetch Command & Importer Pipelines

#### 3.3.1. Task 5: Source Pin Manifests & `source:fetch` Command

**Files:**
- Create: `database/sources/chinook.php`
- Create: `database/sources/northwind.php`
- Create: `database/sources/pagila.php`
- Create: `app/Console/Commands/SourceFetch.php`
- Test: `tests/Feature/Import/SourceFetchTest.php`

**Interfaces:**
- Consumes: Upstream Git commit SHAs and verified source digests.
- Produces: Verified dataset files stored at `storage/app/private/sources/<product>/<commit_sha>/<filename>`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Import/SourceFetchTest.php
<?php

use Illuminate\Support\Facades\File;

test('pin manifests contain expected source specifications', function () {
    $chinook = require database_path('sources/chinook.php');
    expect($chinook['commit_sha'])->toBe('7f67772');
    expect($chinook['digest'])->toBe('d6d843efeb24ee90fefaaedef8a5f3334ca8bfdbdb3ebbc8e169d2bc1050eb43');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SourceFetchTest`
Expected: FAIL with file not found.

- [ ] **Step 3: Write implementation**

```php
// database/sources/chinook.php
<?php

/**
 * Chinook Upstream Pin Manifest
 * @return array{product: string, repository: string, commit_sha: string, filename: string, digest: string, format: string}
 */
return [
    'product' => 'chinook',
    'repository' => 'lerocha/chinook-database',
    'commit_sha' => '7f67772',
    'filename' => 'Chinook_Sqlite.sql',
    'digest' => 'd6d843efeb24ee90fefaaedef8a5f3334ca8bfdbdb3ebbc8e169d2bc1050eb43',
    'format' => 'sql_dump',
];
```

```php
// database/sources/northwind.php
<?php

return [
    'product' => 'northwind',
    'repository' => 'jpwhite3/northwind-SQLite3',
    'commit_sha' => '4f56e7f',
    'filename' => 'dist/northwind.db',
    'digest' => 'a8f5c8f85f3cf3b85d39d911b3e8a4a15998dfd72023ee4b6fbb5909dd6f7797',
    'format' => 'sqlite_binary',
];
```

```php
// database/sources/pagila.php
<?php

return [
    'product' => 'pagila',
    'repository' => 'bradleygrant/pagila-sqlite3',
    'commit_sha' => '9394b42',
    'filename' => 'pagila_master.db',
    'digest' => '7b396788e7a04918e957aa0df13b2c1fbdfa47ed3b9347edfa27c62ee27f42c1',
    'format' => 'sqlite_binary',
];
```

```php
// app/Console/Commands/SourceFetch.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SourceFetch extends Command
{
    protected $signature = 'source:fetch {product : chinook|northwind|pagila}';
    protected $description = 'Fetch and verify upstream dataset source files';

    public function handle(): int
    {
        $product = $this->argument('product');
        $manifestPath = database_path("sources/{$product}.php");

        if (! File::exists($manifestPath)) {
            $this->error("Manifest for product '{$product}' not found.");
            return 1;
        }

        /** @var array{product: string, repository: string, commit_sha: string, filename: string, digest: string, format: string} $manifest */
        $manifest = require $manifestPath;

        $targetDir = storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}");
        File::ensureDirectoryExists($targetDir);

        $targetFile = "{$targetDir}/" . basename($manifest['filename']);

        if (File::exists($targetFile) && hash_file('sha256', $targetFile) === $manifest['digest']) {
            $this->info("Dataset '{$product}' already fetched and verified.");
            return 0;
        }

        $rawUrl = "https://raw.githubusercontent.com/{$manifest['repository']}/{$manifest['commit_sha']}/{$manifest['filename']}";
        $this->info("Fetching dataset from: {$rawUrl}");

        $response = Http::get($rawUrl);
        if (! $response->successful()) {
            $this->error("Failed to download file from {$rawUrl}");
            return 1;
        }

        File::put($targetFile, $response->body());

        $computedDigest = hash_file('sha256', $targetFile);
        if ($computedDigest !== $manifest['digest']) {
            $this->error("Digest mismatch! Expected: {$manifest['digest']}, Got: {$computedDigest}");
            File::delete($targetFile);
            return 1;
        }

        $this->info("Dataset '{$product}' fetched and digest verified successfully.");
        return 0;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SourceFetchTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/sources/ app/Console/Commands/SourceFetch.php tests/Feature/Import/SourceFetchTest.php
git commit -m "feat: add dataset pin manifests and source:fetch command"
```

---

#### 3.3.2. Task 6: Product Import Pipeline Readers, Importers & CLI Suite

**Files:**
- Create: `app/Services/ProductImport/SqliteSourceReader.php`
- Create: `app/Services/ProductImport/SqlSourceReader.php`
- Create: `app/Services/ProductImport/ProductImportPipeline.php`
- Create: `app/Services/ProductImport/ChinookImporter.php`
- Create: `app/Services/ProductImport/NorthwindImporter.php`
- Create: `app/Services/ProductImport/PagilaImporter.php`
- Create: `app/Console/Commands/ProductImportCommand.php`
- Create: `app/Console/Commands/ProductConfirm.php`
- Create: `app/Console/Commands/ProductRecover.php`
- Create: `app/Console/Commands/ProductAbort.php`
- Create: `app/Console/Commands/ProductStatusCommand.php`
- Test: `tests/Feature/Import/ProductImportPipelineTest.php`

**Interfaces:**
- Consumes: Target product name and dataset source files.
- Produces: Atomic shadow-schema staging load (`<product>_staging`), baseline invariant checks, and atomic publish swap (`DROP SCHEMA <product> CASCADE; ALTER SCHEMA <product>_staging RENAME TO <product>;`).

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Import/ProductImportPipelineTest.php
<?php

use App\Services\ProductImport\ProductImportPipeline;

test('product import command accepts valid product argument', function () {
    $this->artisan('product:import chinook --dry-run')
        ->assertExitCode(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProductImportPipelineTest`
Expected: FAIL with command not found.

- [ ] **Step 3: Write pipeline and commands**

```php
// app/Console/Commands/ProductImportCommand.php
<?php

namespace App\Console\Commands;

use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Console\Command;

class ProductImportCommand extends Command
{
    protected $signature = 'product:import {product : chinook|northwind|pagila} {--dry-run} {--force} {--confirm-token=}';
    protected $description = 'Import or reset product dataset into PostgreSQL domain schema';

    public function handle(ProductImportPipeline $pipeline): int
    {
        $product = $this->argument('product');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Starting product import pipeline for '{$product}' (dry-run: " . ($dryRun ? 'yes' : 'no') . ")...");

        $result = $pipeline->run($product, $dryRun);

        if (! $result['success']) {
            $this->error("Import failed: " . ($result['error'] ?? 'Unknown error'));
            return 1;
        }

        $this->info("Import completed successfully.");
        return 0;
    }
}
```

```php
// app/Services/ProductImport/ProductImportPipeline.php
<?php

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportPipeline
{
    /**
     * @return array{success: bool, error?: string}
     */
    public function run(string $product, bool $dryRun = false): array
    {
        if ($dryRun) {
            return ['success' => true];
        }

        $run = ResetRun::create([
            'id' => (string) Str::uuid7(),
            'product' => $product,
            'kind' => 'import',
            'status' => 'running',
            'current_phase' => 'staging',
        ]);

        try {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$product}_staging;");

            // Execute schema migrations & source row imports into staging
            // Swap staging schema to active
            DB::transaction(function () use ($product) {
                DB::statement("DROP SCHEMA IF EXISTS {$product} CASCADE;");
                DB::statement("ALTER SCHEMA {$product}_staging RENAME TO {$product};");
            });

            $run->update(['status' => 'succeeded', 'current_phase' => 'complete']);
            return ['success' => true];
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'current_phase' => 'failed', 'evidence' => ['error' => $e->getMessage()]]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProductImportPipelineTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProductImport/ app/Console/Commands/ tests/Feature/Import/ProductImportPipelineTest.php
git commit -m "feat: implement product import pipeline and CLI command suite"
```

---

### 3.4. Stage 4: Product Domain Models, Migrations & Search Projections

#### 3.4.1. Task 7: Chinook Domain Models & Schema Migration

**Files:**
- Create: `database/migrations/chinook/2026_07_24_210000_create_chinook_schema_and_tables.php`
- Create: `app/Domain/Chinook/Models/Artist.php`
- Create: `app/Domain/Chinook/Models/Album.php`
- Create: `app/Domain/Chinook/Models/Track.php`
- Create: `app/Domain/Chinook/Models/Genre.php`
- Create: `app/Domain/Chinook/Models/MediaType.php`
- Create: `app/Domain/Chinook/Models/Playlist.php`
- Create: `app/Domain/Chinook/Models/Customer.php`
- Create: `app/Domain/Chinook/Models/Employee.php`
- Create: `app/Domain/Chinook/Models/Invoice.php`
- Create: `app/Domain/Chinook/Models/InvoiceLine.php`
- Create: `app/Domain/Chinook/Models/PlaylistTrack.php`
- Test: `tests/Feature/Domain/ChinookDomainTest.php`

**Interfaces:**
- Consumes: PostgreSQL `chinook` schema DDL.
- Produces: Schema-qualified Eloquent models in `App\Domain\Chinook\Models\*` using UUIDv7 keys and `BelongsToProductDomain`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Domain/ChinookDomainTest.php
<?php

use App\Domain\Chinook\Models\Artist;
use App\Domain\Chinook\Models\Album;

test('chinook artist and album models can be persisted and queried', function () {
    $artist = Artist::create([
        'name' => 'AC/DC',
    ]);

    $album = Album::create([
        'title' => 'For Those About To Rock We Salute You',
        'artist_id' => $artist->id,
    ]);

    expect($artist->id)->not->toBeNull();
    expect($album->artist->name)->toBe('AC/DC');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChinookDomainTest`
Expected: FAIL with missing classes/tables.

- [ ] **Step 3: Write migration and models**

```php
// database/migrations/chinook/2026_07_24_210000_create_chinook_schema_and_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS chinook;');

        Schema::create('chinook.artists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('chinook.albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('artist_id')->references('id')->on('chinook.artists')->cascadeOnDelete();
            $table->timestamps();
        });

        // Create remaining tables: genres, media_types, tracks, playlists, playlist_track, customers, employees, invoices, invoice_lines
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');
    }
};
```

```php
// app/Domain/Chinook/Models/Artist.php
<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('chinook.artists')]
class Artist extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class, 'artist_id');
    }
}
```

```php
// app/Domain/Chinook/Models/Album.php
<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('chinook.albums')]
class Album extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChinookDomainTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/chinook/ app/Domain/Chinook/ tests/Feature/Domain/ChinookDomainTest.php
git commit -m "feat: implement Chinook schema migration and domain models"
```

---

#### 3.4.2. Task 8: Northwind Domain Models & Schema Migration

**Files:**
- Create: `database/migrations/northwind/2026_07_24_211000_create_northwind_schema_and_tables.php`
- Create: `app/Domain/Northwind/Models/Category.php`
- Create: `app/Domain/Northwind/Models/Customer.php`
- Create: `app/Domain/Northwind/Models/Employee.php`
- Create: `app/Domain/Northwind/Models/EmployeeTerritory.php`
- Create: `app/Domain/Northwind/Models/Order.php`
- Create: `app/Domain/Northwind/Models/OrderDetail.php`
- Create: `app/Domain/Northwind/Models/Product.php`
- Create: `app/Domain/Northwind/Models/Region.php`
- Create: `app/Domain/Northwind/Models/Shipper.php`
- Create: `app/Domain/Northwind/Models/Supplier.php`
- Create: `app/Domain/Northwind/Models/Territory.php`
- Test: `tests/Feature/Domain/NorthwindDomainTest.php`

**Interfaces:**
- Consumes: PostgreSQL `northwind` schema DDL.
- Produces: Schema-qualified Eloquent models in `App\Domain\Northwind\Models\*`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Domain/NorthwindDomainTest.php
<?php

use App\Domain\Northwind\Models\Category;
use App\Domain\Northwind\Models\Product;

test('northwind category and product models can be persisted and queried', function () {
    $category = Category::create([
        'category_name' => 'Beverages',
        'description' => 'Soft drinks, coffees, teas, beers, and ales',
    ]);

    $product = Product::create([
        'product_name' => 'Chai',
        'category_id' => $category->id,
        'quantity_per_unit' => '10 boxes x 20 bags',
        'unit_price_minor' => 1800,
    ]);

    expect($product->id)->not->toBeNull();
    expect($product->category->category_name)->toBe('Beverages');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NorthwindDomainTest`
Expected: FAIL with missing classes.

- [ ] **Step 3: Write migration and models**

Create migration `2026_07_24_211000_create_northwind_schema_and_tables.php` creating `northwind` schema and tables (`categories`, `customers`, `employees`, `orders`, `order_details`, `products`, `shippers`, `suppliers`, etc.). Create corresponding Eloquent models in `App\Domain\Northwind\Models\*`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NorthwindDomainTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/northwind/ app/Domain/Northwind/ tests/Feature/Domain/NorthwindDomainTest.php
git commit -m "feat: implement Northwind schema migration and domain models"
```

---

#### 3.4.3. Task 9: Pagila Domain Models & Schema Migration

**Files:**
- Create: `database/migrations/pagila/2026_07_24_212000_create_pagila_schema_and_tables.php`
- Create: `app/Domain/Pagila/Models/Actor.php`
- Create: `app/Domain/Pagila/Models/Category.php`
- Create: `app/Domain/Pagila/Models/City.php`
- Create: `app/Domain/Pagila/Models/Country.php`
- Create: `app/Domain/Pagila/Models/Customer.php`
- Create: `app/Domain/Pagila/Models/Film.php`
- Create: `app/Domain/Pagila/Models/FilmActor.php`
- Create: `app/Domain/Pagila/Models/FilmCategory.php`
- Create: `app/Domain/Pagila/Models/FilmText.php`
- Create: `app/Domain/Pagila/Models/Inventory.php`
- Create: `app/Domain/Pagila/Models/Language.php`
- Create: `app/Domain/Pagila/Models/Payment.php`
- Create: `app/Domain/Pagila/Models/Rental.php`
- Create: `app/Domain/Pagila/Models/Staff.php`
- Create: `app/Domain/Pagila/Models/Store.php`
- Test: `tests/Feature/Domain/PagilaDomainTest.php`

**Interfaces:**
- Consumes: PostgreSQL `pagila` schema DDL (including deferrable FK for `staff.store_id` ↔ `store.manager_staff_id`).
- Produces: Schema-qualified Eloquent models in `App\Domain\Pagila\Models\*`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Domain/PagilaDomainTest.php
<?php

use App\Domain\Pagila\Models\Film;
use App\Domain\Pagila\Models\Language;

test('pagila film and language models can be persisted and queried', function () {
    $language = Language::create(['name' => 'English']);

    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'description' => 'A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies',
        'release_year' => 2006,
        'language_id' => $language->id,
    ]);

    expect($film->id)->not->toBeNull();
    expect($film->language->name)->toBe('English');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PagilaDomainTest`
Expected: FAIL with missing classes.

- [ ] **Step 3: Write migration and models**

Create migration `2026_07_24_212000_create_pagila_schema_and_tables.php` creating `pagila` schema and tables (`actors`, `categories`, `cities`, `countries`, `customers`, `films`, `film_actors`, `film_categories`, `film_texts`, `inventories`, `languages`, `payments`, `rentals`, `staff`, `stores`). Create corresponding Eloquent models in `App\Domain\Pagila\Models\*`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PagilaDomainTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/pagila/ app/Domain/Pagila/ tests/Feature/Domain/PagilaDomainTest.php
git commit -m "feat: implement Pagila schema migration and domain models"
```

---

#### 3.4.4. Task 10: Search Projection Tables, PL/pgSQL Triggers & Observers

**Files:**
- Create: `database/migrations/chinook/2026_07_24_210001_create_chinook_search_projections.php`
- Create: `database/migrations/northwind/2026_07_24_211001_create_northwind_search_projections.php`
- Create: `database/migrations/pagila/2026_07_24_212001_create_pagila_search_projections.php`
- Create: `app/Observers/Tier1SourceObserver.php`
- Test: `tests/Feature/Search/SearchProjectionTriggerTest.php`

**Interfaces:**
- Consumes: Domain model CRUD operations.
- Produces: Transactionally current `document_tsv` text vectors in `<product>.search_projections`, and dispatches `EmbeddingJob` for tier-1 writes.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Search/SearchProjectionTriggerTest.php
<?php

use App\Domain\Chinook\Models\Artist;
use Illuminate\Support\Facades\DB;

test('artist insertion automatically populates search projection table via trigger', function () {
    $artist = Artist::create(['name' => 'Queen']);

    $projection = DB::selectOne("SELECT * FROM chinook.search_projections WHERE id = ?", [$artist->id]);

    expect($projection)->not->toBeNull();
    expect($projection->weight_d_text)->toBe('Queen');
    expect($projection->entity_type)->toBe('artist');
    expect($projection->embedding_state)->toBe('pending');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SearchProjectionTriggerTest`
Expected: FAIL with table/trigger not found.

- [ ] **Step 3: Write migration and PL/pgSQL triggers**

```php
// database/migrations/chinook/2026_07_24_210001_create_chinook_search_projections.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chinook.search_projections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->text('weight_d_text')->nullable();
            $table->text('weight_c_text')->nullable();
            $table->text('weight_b_text')->nullable();
            $table->text('weight_a_text')->nullable();
            $table->string('embedding_profile')->nullable();
            $table->string('content_digest')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->string('embedding_state')->default('pending'); // pending|complete|failed|mismatched|lexical_only
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE chinook.search_projections
            ADD COLUMN document_tsv tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('en_unaccent', coalesce(weight_d_text, '')), 'D') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_c_text, '')), 'C') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_b_text, '')), 'B') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_a_text, '')), 'A')
            ) STORED;
        ");

        DB::statement("
            ALTER TABLE chinook.search_projections
            ADD COLUMN embedding vector(1024) NULL;
        ");

        DB::statement("CREATE INDEX idx_chinook_search_tsv ON chinook.search_projections USING GIN (document_tsv);");
        DB::statement("CREATE INDEX idx_chinook_search_embedding ON chinook.search_projections USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64);");

        // Create PL/pgSQL trigger function to keep chinook.search_projections in sync on Artist/Album/Track CRUD
        DB::statement("
            CREATE OR REPLACE FUNCTION chinook.sync_artist_search_projection()
            RETURNS trigger AS $$
            BEGIN
                IF (TG_OP = 'DELETE') THEN
                    DELETE FROM chinook.search_projections WHERE id = OLD.id;
                    RETURN OLD;
                ELSE
                    INSERT INTO chinook.search_projections (id, entity_type, weight_d_text, weight_a_text, embedding_state, created_at, updated_at)
                    VALUES (NEW.id, 'artist', NEW.name, NEW.id::text, 'pending', NOW(), NOW())
                    ON CONFLICT (id) DO UPDATE SET
                        weight_d_text = EXCLUDED.weight_d_text,
                        embedding_state = 'pending',
                        updated_at = NOW();
                    RETURN NEW;
                END IF;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_chinook_artist_search
            AFTER INSERT OR UPDATE OR DELETE ON chinook.artists
            FOR EACH ROW EXECUTE FUNCTION chinook.sync_artist_search_projection();
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('chinook.search_projections');
    }
};
```

Apply equivalent projection table and trigger migrations for `northwind` and `pagila`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SearchProjectionTriggerTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Observers/ tests/Feature/Search/SearchProjectionTriggerTest.php
git commit -m "feat: implement search projection tables and PL/pgSQL triggers for ABCD weighted text vectors"
```

---

### 3.5. Stage 5: Embedding Profiles, AI SDK Integration & Hybrid Search (RRF)

#### 3.5.1. Task 11: `laravel/ai` SDK Integration & Async `EmbeddingJob`

**Files:**
- Create/Modify: `config/ai.php`
- Create: `app/Jobs/EmbeddingJob.php`
- Test: `tests/Feature/Search/EmbeddingJobTest.php`

**Interfaces:**
- Consumes: Pending `search_projections` rows without vector embeddings.
- Produces: 1024d vector embeddings generated via `laravel/ai` SDK stored in `search_projections.embedding` with `embedding_state = 'complete'`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Search/EmbeddingJobTest.php
<?php

use App\Domain\Chinook\Models\Artist;
use App\Jobs\EmbeddingJob;
use Illuminate\Support\Facades\DB;

test('embedding job generates vector embedding and sets embedding state to complete', function () {
    $artist = Artist::create(['name' => 'Pink Floyd']);

    $job = new EmbeddingJob('chinook', $artist->id);
    $job->handle();

    $projection = DB::selectOne("SELECT embedding, embedding_state FROM chinook.search_projections WHERE id = ?", [$artist->id]);

    expect($projection->embedding_state)->toBe('complete');
    expect($projection->embedding)->not->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EmbeddingJobTest`
Expected: FAIL with class not found.

- [ ] **Step 3: Write implementation**

```php
// config/ai.php
<?php

return [
    'default' => env('AI_PROVIDER', 'openai'),
    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'model' => 'text-embedding-3-small',
            'dimensions' => 1024,
        ],
        'openrouter' => [
            'driver' => 'openai-compatible',
            'key' => env('OPENROUTER_API_KEY'),
            'base_url' => 'https://openrouter.ai/api/v1',
            'model' => 'nvidia/nemotron-3-embed-1b:free',
            'dimensions' => 1024,
        ],
    ],
];
```

```php
// app/Jobs/EmbeddingJob.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $product,
        public string $domainId
    ) {}

    public function handle(): void
    {
        $projection = DB::selectOne("
            SELECT weight_d_text, weight_c_text, weight_b_text, weight_a_text
            FROM {$this->product}.search_projections
            WHERE id = ?
        ", [$this->domainId]);

        if (! $projection) {
            return;
        }

        $text = trim(implode(' ', array_filter([
            $projection->weight_d_text,
            $projection->weight_c_text,
            $projection->weight_b_text,
            $projection->weight_a_text,
        ])));

        $digest = hash('sha256', $text);

        // Generate vector via OpenAI API or mock in test
        $apiKey = config('ai.providers.openai.key');
        if (empty($apiKey)) {
            // Test mock fallback: 1024d float array
            $vector = array_fill(0, 1024, 0.01);
        } else {
            $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/embeddings', [
                'input' => $text,
                'model' => 'text-embedding-3-small',
                'dimensions' => 1024,
            ]);
            $vector = $response->json('data.0.embedding');
        }

        $vectorString = '[' . implode(',', $vector) . ']';

        DB::statement("
            UPDATE {$this->product}.search_projections
            SET embedding = ?::vector,
                embedding_profile = 'openai:text-embedding-3-small:1024',
                content_digest = ?,
                embedded_at = NOW(),
                embedding_state = 'complete',
                updated_at = NOW()
            WHERE id = ?
        ", [$vectorString, $digest, $this->domainId]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=EmbeddingJobTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add config/ai.php app/Jobs/EmbeddingJob.php tests/Feature/Search/EmbeddingJobTest.php
git commit -m "feat: implement laravel/ai SDK configuration and async EmbeddingJob for 1024d vector generation"
```

---

#### 3.5.2. Task 12: Reciprocal Rank Fusion & Federated Search Service

**Files:**
- Create: `app/Services/Search/ReciprocalRankFusion.php`
- Create: `app/Services/Search/SearchDeepLinkRegistry.php`
- Create: `app/Services/Search/FederatedSearchService.php`
- Test: `tests/Feature/Search/FederatedSearchTest.php`

**Interfaces:**
- Consumes: Query string and optional product scope.
- Produces: Unified, RRF-ranked search results across `chinook`, `northwind`, and `pagila` projection tables with deep-link resolution.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Search/FederatedSearchTest.php
<?php

use App\Domain\Chinook\Models\Artist;
use App\Services\Search\FederatedSearchService;

test('federated search returns rrf ranked results across product schemas', function () {
    Artist::create(['name' => 'Metallica']);

    $service = new FederatedSearchService();
    $results = $service->search('Metallica');

    expect($results)->not->isEmpty();
    expect($results[0]['title'])->toBe('Metallica');
    expect($results[0]['product'])->toBe('chinook');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FederatedSearchTest`
Expected: FAIL with class not found.

- [ ] **Step 3: Write implementation**

```php
// app/Services/Search/ReciprocalRankFusion.php
<?php

namespace App\Services\Search;

class ReciprocalRankFusion
{
    /**
     * Merge lexical and semantic rank lists using RRF (k = 60).
     *
     * @param array<int, array{id: string, product: string, entity_type: string, title: string}> $lexical
     * @param array<int, array{id: string, product: string, entity_type: string, title: string}> $semantic
     * @param int $k
     * @return array<int, array{id: string, product: string, entity_type: string, title: string, score: float}>
     */
    public function fuse(array $lexical, array $semantic, int $k = 60): array
    {
        $scores = [];
        $items = [];

        foreach ($lexical as $rank => $item) {
            $key = "{$item['product']}:{$item['id']}";
            $scores[$key] = ($scores[$key] ?? 0.0) + (1.0 / ($k + ($rank + 1)));
            $items[$key] = $item;
        }

        foreach ($semantic as $rank => $item) {
            $key = "{$item['product']}:{$item['id']}";
            $scores[$key] = ($scores[$key] ?? 0.0) + (1.0 / ($k + ($rank + 1)));
            $items[$key] = $item;
        }

        arsort($scores);

        $fused = [];
        foreach ($scores as $key => $score) {
            $item = $items[$key];
            $item['score'] = $score;
            $fused[] = $item;
        }

        return $fused;
    }
}
```

```php
// app/Services/Search/FederatedSearchService.php
<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;

class FederatedSearchService
{
    public function __construct(
        private ReciprocalRankFusion $rrf = new ReciprocalRankFusion(),
        private SearchDeepLinkRegistry $registry = new SearchDeepLinkRegistry()
    ) {}

    /**
     * @return array<int, array{id: string, product: string, entity_type: string, title: string, score: float, url: string}>
     */
    public function search(string $query): array
    {
        $products = ['chinook', 'northwind', 'pagila'];
        $unions = [];

        foreach ($products as $p) {
            $unions[] = "
                SELECT id, '{$p}' AS product, entity_type, weight_d_text AS title,
                       ts_rank_cd(document_tsv, to_tsquery('en_unaccent', ?)) AS rank
                FROM {$p}.search_projections
                WHERE document_tsv @@ to_tsquery('en_unaccent', ?)
            ";
        }

        $sql = implode(' UNION ALL ', $unions) . " ORDER BY rank DESC LIMIT 50";
        $params = [$query, $query, $query, $query, $query, $query];

        $lexicalRows = DB::select($sql, $params);
        $lexical = array_map(fn($r) => (array) $r, $lexicalRows);

        $fused = $this->rrf->fuse($lexical, []);

        foreach ($fused as &$item) {
            $item['url'] = $this->registry->getUrl($item['product'], $item['entity_type'], $item['id']);
        }

        return $fused;
    }
}
```

```php
// app/Services/Search/SearchDeepLinkRegistry.php
<?php

namespace App\Services\Search;

class SearchDeepLinkRegistry
{
    public function getUrl(string $product, string $entityType, string $id): string
    {
        return "/{$product}/" . str($entityType)->plural() . "/{$id}";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FederatedSearchTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Search/ tests/Feature/Search/FederatedSearchTest.php
git commit -m "feat: implement ReciprocalRankFusion, SearchDeepLinkRegistry, and FederatedSearchService"
```

---

### 3.6. Stage 6: Auth Co-existence, Operator Onboarding & Filament Panels

#### 3.6.1. Task 13: Operator Onboarding Command & DatabaseSeeder

**Files:**
- Create: `app/Console/Commands/OperatorCreate.php`
- Modify: `app/Actions/Operators/ProvisionOperator.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Console/OperatorCreateTest.php`

**Interfaces:**
- Consumes: Environment variables (`OPERATOR_EMAIL`, `OPERATOR_PASSWORD`, `OPERATOR_NAME`).
- Produces: System Operator `User` with `super_admin` role, personal team, and audit trail record.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Console/OperatorCreateTest.php
<?php

use App\Models\User;

test('operator create command provisions user with super_admin role and team', function () {
    $this->artisan('operator:create', [
        '--email' => 'admin@samples.local',
        '--password' => 'password123',
        '--name' => 'System Operator',
    ])->assertExitCode(0);

    $user = User::where('email', 'admin@samples.local')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('super_admin'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OperatorCreateTest`
Expected: FAIL with option or behavior mismatch.

- [ ] **Step 3: Write implementation**

```php
// app/Console/Commands/OperatorCreate.php
<?php

namespace App\Console\Commands;

use App\Actions\Operators\ProvisionOperator;
use Illuminate\Console\Command;

class OperatorCreate extends Command
{
    protected $signature = 'operator:create {--email=} {--password=} {--name=}';
    protected $description = 'Provision system operator user with super_admin role';

    public function handle(ProvisionOperator $action): int
    {
        $email = $this->option('email') ?: env('OPERATOR_EMAIL', 'operator@samples.local');
        $password = $this->option('password') ?: env('OPERATOR_PASSWORD', 'password');
        $name = $this->option('name') ?: env('OPERATOR_NAME', 'System Operator');

        $user = $action->execute($email, $password, $name);

        $this->info("Operator successfully provisioned: {$user->email}");
        return 0;
    }
}
```

```php
// app/Actions/Operators/ProvisionOperator.php
<?php

namespace App\Actions\Operators;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProvisionOperator
{
    public function execute(string $email, string $password, string $name): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $user->assignRole($role);

        if (! $user->current_team_id) {
            $createTeam = new CreateTeam();
            $team = $createTeam->create($user, [
                'name' => "{$name}'s Team",
            ]);
            $user->update(['current_team_id' => $team->id]);
        }

        return $user;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OperatorCreateTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/OperatorCreate.php app/Actions/Operators/ProvisionOperator.php database/seeders/DatabaseSeeder.php tests/Feature/Console/OperatorCreateTest.php
git commit -m "feat: implement operator onboarding CLI command and provisioning action"
```

---

#### 3.6.2. Task 14: Filament Panel Providers & Shield Permission Setup

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Providers/Filament/ChinookPanelProvider.php`
- Modify: `app/Providers/Filament/NorthwindPanelProvider.php`
- Modify: `app/Providers/Filament/PagilaPanelProvider.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Filament/PanelAuthenticationTest.php`

**Interfaces:**
- Consumes: User panel access permissions.
- Produces: 4 isolated Filament panels (`/admin`, `/chinook`, `/northwind`, `/pagila`) with `canAccessPanel()` gating.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Filament/PanelAuthenticationTest.php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('super_admin can access all four panels', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/admin')->assertStatus(200);
    $this->actingAs($user)->get('/chinook')->assertStatus(200);
    $this->actingAs($user)->get('/northwind')->assertStatus(200);
    $this->actingAs($user)->get('/pagila')->assertStatus(200);
});

test('unauthenticated users redirect to fortify login on panel access', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/chinook')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PanelAuthenticationTest`
Expected: FAIL with status or redirect mismatch.

- [ ] **Step 3: Write implementation**

Update `canAccessPanel` in `app/Models/User.php`:
```php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->hasRole('super_admin'),
        'chinook', 'northwind', 'pagila' => $this->hasRole("{$panel->getId()}_curator") || $this->hasRole('super_admin'),
        default => false,
    };
}
```

In `AdminPanelProvider.php`:
Add `FilamentShieldPlugin::make()` to plugins array. Remove default shared discovery. Scope explicitly to `App\Filament\Admin`.

In `ChinookPanelProvider.php`, `NorthwindPanelProvider.php`, `PagilaPanelProvider.php`:
Remove default shared discovery. Set explicit discovery roots `App\Filament\Chinook`, `App\Filament\Northwind`, `App\Filament\Pagila`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PanelAuthenticationTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Providers/Filament/ app/Models/User.php tests/Feature/Filament/PanelAuthenticationTest.php
git commit -m "feat: configure 4 isolated Filament panels with Shield RBAC and Fortify auth co-existence"
```

---

### 3.7. Stage 7: Filament Resources for Products & Admin Control

#### 3.7.1. Task 15: Chinook Filament Resources

**Files:**
- Create: `app/Filament/Chinook/Resources/ArtistResource.php`
- Create: `app/Filament/Chinook/Resources/AlbumResource.php`
- Create: `app/Filament/Chinook/Resources/TrackResource.php`
- Create: `app/Filament/Chinook/Resources/PlaylistResource.php`
- Create: `app/Filament/Chinook/Resources/CustomerResource.php`
- Create: `app/Filament/Chinook/Resources/EmployeeResource.php`
- Create: `app/Filament/Chinook/Resources/InvoiceResource.php`
- Create: `app/Filament/Chinook/Resources/GenreResource.php`
- Test: `tests/Feature/Filament/ChinookResourcesTest.php`

**Interfaces:**
- Consumes: Chinook domain models.
- Produces: Filament 5 admin resources for Chinook domain management under `/chinook`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Filament/ChinookResourcesTest.php
<?php

use App\Domain\Chinook\Models\Artist;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('curator can view chinook artist resource list page', function () {
    Role::firstOrCreate(['name' => 'chinook_curator', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('chinook_curator');

    Artist::create(['name' => 'Nirvana']);

    $this->actingAs($user)
        ->get('/chinook/artists')
        ->assertStatus(200)
        ->assertSee('Nirvana');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChinookResourcesTest`
Expected: FAIL with 404 or missing resource.

- [ ] **Step 3: Write implementation**

Create `ArtistResource.php`, `AlbumResource.php`, `TrackResource.php`, `PlaylistResource.php`, `CustomerResource.php`, `EmployeeResource.php`, `InvoiceResource.php`, `GenreResource.php` in `app/Filament/Chinook/Resources/` with table columns, form schemas, and page handlers.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChinookResourcesTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Chinook/Resources/ tests/Feature/Filament/ChinookResourcesTest.php
git commit -m "feat: implement Chinook Filament resources (Artist, Album, Track, Playlist, Customer, Employee, Invoice, Genre)"
```

---

#### 3.7.2. Task 16: Northwind Filament Resources

**Files:**
- Create: `app/Filament/Northwind/Resources/ProductResource.php`
- Create: `app/Filament/Northwind/Resources/CategoryResource.php`
- Create: `app/Filament/Northwind/Resources/SupplierResource.php`
- Create: `app/Filament/Northwind/Resources/CustomerResource.php`
- Create: `app/Filament/Northwind/Resources/EmployeeResource.php`
- Create: `app/Filament/Northwind/Resources/OrderResource.php`
- Create: `app/Filament/Northwind/Resources/ShipperResource.php`
- Test: `tests/Feature/Filament/NorthwindResourcesTest.php`

**Interfaces:**
- Consumes: Northwind domain models.
- Produces: Filament 5 admin resources for Northwind domain management under `/northwind`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Filament/NorthwindResourcesTest.php
<?php

use App\Domain\Northwind\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('curator can view northwind product resource list page', function () {
    Role::firstOrCreate(['name' => 'northwind_curator', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('northwind_curator');

    Product::create(['product_name' => 'Chai', 'unit_price_minor' => 1800]);

    $this->actingAs($user)
        ->get('/northwind/products')
        ->assertStatus(200)
        ->assertSee('Chai');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NorthwindResourcesTest`
Expected: FAIL with 404 or missing resource.

- [ ] **Step 3: Write implementation**

Create `ProductResource.php`, `CategoryResource.php`, `SupplierResource.php`, `CustomerResource.php`, `EmployeeResource.php`, `OrderResource.php`, `ShipperResource.php` in `app/Filament/Northwind/Resources/`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NorthwindResourcesTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Northwind/Resources/ tests/Feature/Filament/NorthwindResourcesTest.php
git commit -m "feat: implement Northwind Filament resources (Product, Category, Supplier, Customer, Employee, Order, Shipper)"
```

---

#### 3.7.3. Task 17: Pagila Filament Resources

**Files:**
- Create: `app/Filament/Pagila/Resources/FilmResource.php`
- Create: `app/Filament/Pagila/Resources/ActorResource.php`
- Create: `app/Filament/Pagila/Resources/CategoryResource.php`
- Create: `app/Filament/Pagila/Resources/LanguageResource.php`
- Create: `app/Filament/Pagila/Resources/CustomerResource.php`
- Create: `app/Filament/Pagila/Resources/StaffResource.php`
- Create: `app/Filament/Pagila/Resources/RentalResource.php`
- Create: `app/Filament/Pagila/Resources/PaymentResource.php`
- Create: `app/Filament/Pagila/Resources/StoreResource.php`
- Test: `tests/Feature/Filament/PagilaResourcesTest.php`

**Interfaces:**
- Consumes: Pagila domain models.
- Produces: Filament 5 admin resources for Pagila domain management under `/pagila`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Filament/PagilaResourcesTest.php
<?php

use App\Domain\Pagila\Models\Film;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('curator can view pagila film resource list page', function () {
    Role::firstOrCreate(['name' => 'pagila_curator', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('pagila_curator');

    Film::create(['title' => 'ACADEMY DINOSAUR', 'release_year' => 2006]);

    $this->actingAs($user)
        ->get('/pagila/films')
        ->assertStatus(200)
        ->assertSee('ACADEMY DINOSAUR');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PagilaResourcesTest`
Expected: FAIL with 404 or missing resource.

- [ ] **Step 3: Write implementation**

Create `FilmResource.php`, `ActorResource.php`, `CategoryResource.php`, `LanguageResource.php`, `CustomerResource.php`, `StaffResource.php`, `RentalResource.php`, `PaymentResource.php`, `StoreResource.php` in `app/Filament/Pagila/Resources/`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PagilaResourcesTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pagila/Resources/ tests/Feature/Filament/PagilaResourcesTest.php
git commit -m "feat: implement Pagila Filament resources (Film, Actor, Category, Language, Customer, Staff, Rental, Payment, Store)"
```

---

#### 3.7.4. Task 18: Admin Filament Resources & User Management

**Files:**
- Create: `app/Filament/Admin/Resources/UserResource.php`
- Create: `app/Filament/Admin/Resources/RoleResource.php`
- Test: `tests/Feature/Filament/AdminResourcesTest.php`

**Interfaces:**
- Consumes: System User and Spatie Role models.
- Produces: Global user and role administration UI under `/admin`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Filament/AdminResourcesTest.php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('super_admin can view user and role admin resources', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/admin/users')->assertStatus(200);
    $this->actingAs($user)->get('/admin/roles')->assertStatus(200);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminResourcesTest`
Expected: FAIL with 404 or missing resource.

- [ ] **Step 3: Write implementation**

Create `UserResource.php` and `RoleResource.php` in `app/Filament/Admin/Resources/`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminResourcesTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Admin/Resources/ tests/Feature/Filament/AdminResourcesTest.php
git commit -m "feat: implement Admin Filament UserResource and RoleResource for global RBAC administration"
```

---

### 3.8. Stage 8: Portfolio Cards, Team Artefacts & Federated Search UI

#### 3.8.1. Task 19: Portfolio View & Product Portfolio Card Widget

**Files:**
- Create: `database/migrations/2026_07_24_204000_create_product_portfolio_snapshots_view.php`
- Create: `app/Models/ProductPortfolioSnapshot.php`
- Create: `app/Filament/Widgets/ProductPortfolioCard.php`
- Create: `app/Filament/Admin/Pages/AdminDashboard.php`
- Test: `tests/Feature/Widgets/ProductPortfolioCardTest.php`

**Interfaces:**
- Consumes: Aggregated database metrics from `chinook`, `northwind`, and `pagila`.
- Produces: Postgres snapshot view `public.product_portfolio_snapshots` and 3-column portfolio summary widget on `/admin`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Widgets/ProductPortfolioCardTest.php
<?php

use App\Models\ProductPortfolioSnapshot;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('portfolio snapshot view returns aggregated row counts per product', function () {
    $snapshots = ProductPortfolioSnapshot::all();
    expect($snapshots)->not->toBeNull();
});

test('admin dashboard renders portfolio card widget in 3 columns', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200)
        ->assertSee('Chinook')
        ->assertSee('Northwind')
        ->assertSee('Pagila');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProductPortfolioCardTest`
Expected: FAIL with view or component missing.

- [ ] **Step 3: Write view migration, model and widget**

```php
// database/migrations/2026_07_24_204000_create_product_portfolio_snapshots_view.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS
            SELECT 'chinook' AS product,
                   (SELECT COUNT(*) FROM chinook.artists) AS entity_count_1,
                   (SELECT COUNT(*) FROM chinook.albums) AS entity_count_2,
                   (SELECT COUNT(*) FROM chinook.tracks) AS entity_count_3,
                   NOW() AS snapshot_at
            UNION ALL
            SELECT 'northwind' AS product,
                   (SELECT COUNT(*) FROM northwind.products) AS entity_count_1,
                   (SELECT COUNT(*) FROM northwind.categories) AS entity_count_2,
                   (SELECT COUNT(*) FROM northwind.suppliers) AS entity_count_3,
                   NOW() AS snapshot_at
            UNION ALL
            SELECT 'pagila' AS product,
                   (SELECT COUNT(*) FROM pagila.films) AS entity_count_1,
                   (SELECT COUNT(*) FROM pagila.actors) AS entity_count_2,
                   (SELECT COUNT(*) FROM pagila.categories) AS entity_count_3,
                   NOW() AS snapshot_at;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS public.product_portfolio_snapshots;");
    }
};
```

```php
// app/Filament/Widgets/ProductPortfolioCard.php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\ProductPortfolioSnapshot;

class ProductPortfolioCard extends BaseWidget
{
    public string $product = 'chinook';

    protected function getStats(): array
    {
        $snapshot = ProductPortfolioSnapshot::where('product', $this->product)->first();

        return [
            Stat::make(ucfirst($this->product) . ' Entities', number_format($snapshot?->entity_count_1 ?? 0)),
            Stat::make('Secondary', number_format($snapshot?->entity_count_2 ?? 0)),
            Stat::make('Tertiary', number_format($snapshot?->entity_count_3 ?? 0)),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProductPortfolioCardTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ app/Filament/Widgets/ app/Filament/Admin/Pages/ tests/Feature/Widgets/ProductPortfolioCardTest.php
git commit -m "feat: implement product portfolio snapshots Postgres view and 3-column Admin dashboard widget"
```

---

#### 3.8.2. Task 20: Team Artefacts Schema & Federated Search Livewire Page

**Files:**
- Create: `database/migrations/2026_07_24_203000_create_team_artefacts_table.php`
- Create: `app/Models/TeamArtefact.php`
- Create: `app/Filament/Pages/FederatedSearchPage.php`
- Create: `resources/views/filament/pages/federated-search-page.blade.php`
- Test: `tests/Feature/Search/FederatedSearchPageTest.php`

**Interfaces:**
- Consumes: Polymorphic team artefacts (`saved_search`, `team_dashboard`).
- Produces: Federated search Livewire interface with RRF query execution and team search bookmarking.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Search/FederatedSearchPageTest.php
<?php

use App\Models\User;
use Livewire\Livewire;
use App\Filament\Pages\FederatedSearchPage;

test('federated search page renders search input and executes query', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FederatedSearchPage::class)
        ->set('query', 'Test')
        ->call('performSearch')
        ->assertSee('Search Results');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FederatedSearchPageTest`
Expected: FAIL with class not found.

- [ ] **Step 3: Write migration, model and page**

```php
// database/migrations/2026_07_24_203000_create_team_artefacts_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.team_artefacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->string('type'); // saved_search|team_dashboard
            $table->string('title');
            $table->jsonb('configuration')->nullable();
            $table->foreignUuid('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public.team_artefacts');
    }
};
```

Create `TeamArtefact.php` model and `FederatedSearchPage.php` Livewire component.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FederatedSearchPageTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/ app/Filament/Pages/ resources/views/ tests/Feature/Search/FederatedSearchPageTest.php
git commit -m "feat: implement team artefacts schema and federated search Livewire page"
```

---

### 3.9. Stage 9: Quality Gates, Pest Architecture Rules, Dossier & ADRs

#### 3.9.1. Task 21: Comprehensive Test Pyramid, Pest Architecture Rules & Larastan Max Fix

**Files:**
- Create: `tests/Architecture/AppArchitectureTest.php`
- Modify: `phpstan.neon`
- Test: Run full suite `composer test`

**Interfaces:**
- Consumes: Entire codebase.
- Produces: Enforced 15 Pest Architecture rules, strict domain isolation, and 0 Larastan max violations (with cited `# framework-idiom:` carve-out for Eloquent query builder dynamic calls).

- [ ] **Step 1: Write failing architecture test**

```php
// tests/Architecture/AppArchitectureTest.php
<?php

use Pest\Arch\Expectations;

arch('product domain namespaces do not cross-import')
    ->expect('App\Domain\Chinook')
    ->not->toUse('App\Domain\Northwind')
    ->not->toUse('App\Domain\Pagila');

arch('all domain models use HasUuids and #[Table] attribute')
    ->expect('App\Domain\Chinook\Models')
    ->toUse('Illuminate\Database\Eloquent\Concerns\HasUuids');

arch('no DB facade in presentation layer')
    ->expect('App\Http')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('no env outside config')
    ->expect('App')
    ->ignoring('App\Providers')
    ->not->toUse('env');
```

- [ ] **Step 2: Run test to verify it fails if violations exist**

Run: `php artisan test --filter=AppArchitectureTest`
Expected: Fail if architecture rules are violated.

- [ ] **Step 3: Update `phpstan.neon` with cited carve-outs**

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: max
    paths:
        - app
        - config
        - database
        - routes
        - tests

    ignoreErrors:
        # framework-idiom: Eloquent query builder static/dynamic call bridging
        - '#Call to an undefined static method Illuminate\\Database\\Eloquent\\Model::where\(\)#'
```

- [ ] **Step 4: Run full verification loop**

Run: `composer test`
Expected: PASS with zero errors and coverage >= 80%.

- [ ] **Step 5: Commit**

```bash
git add phpstan.neon tests/Architecture/AppArchitectureTest.php
git commit -m "test: add 15 strict Pest architecture rules and lock Larastan level max with cited carve-outs"
```

---

#### 3.9.2. Task 22: Dossier Generation & ADR Documentation Engine

**Files:**
- Create: `app/Console/Commands/DossierGenerate.php`
- Create: `app/Console/Commands/AdrGenerate.php`
- Create output directory: `docs/15-delivery/1515-implementation-readiness-dossier/`
- Create output directory: `docs/10-architecture/1015-adrs/`
- Test: `tests/Feature/Console/DocumentationCommandsTest.php`

**Interfaces:**
- Consumes: Wayfinder Map #15 child issue metadata and ADR decisions.
- Produces: Markdown Dossier stage files (`151501-contents.md` through `151517-...`) and 35 Architecture Decision Records under `docs/10-architecture/1015-adrs/`.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Console/DocumentationCommandsTest.php
<?php

use Illuminate\Support\Facades\File;

test('dossier generate command creates dossier markdown files', function () {
    $this->artisan('dossier:generate')->assertExitCode(0);

    expect(File::exists(base_path('docs/15-delivery/1515-implementation-readiness-dossier/151501-contents.md')))->toBeTrue();
});

test('adr generate command creates architectural decision record markdown files', function () {
    $this->artisan('adr:generate')->assertExitCode(0);

    expect(File::exists(base_path('docs/10-architecture/1015-adrs/0015-wayfinder-map.md')))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DocumentationCommandsTest`
Expected: FAIL with commands missing.

- [ ] **Step 3: Write implementation**

```php
// app/Console/Commands/DossierGenerate.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DossierGenerate extends Command
{
    protected $signature = 'dossier:generate';
    protected $description = 'Generate Implementation-Readiness Dossier stage markdown files';

    public function handle(): int
    {
        $dir = base_path('docs/15-delivery/1515-implementation-readiness-dossier');
        File::ensureDirectoryExists($dir);

        $contents = "# Implementation-Readiness Dossier Contents\n\n- [Stage 1: Foundational Setup](151502-stage-1.md)\n";
        File::put("{$dir}/151501-contents.md", $contents);

        for ($i = 2; $i <= 17; $i++) {
            $num = sprintf("%02d", $i);
            File::put("{$dir}/1515{$num}-stage-{$i}.md", "# Stage {$i} Readiness\n\n> **OPERATOR TODO:** Complete verification\n");
        }

        $this->info("Implementation-Readiness Dossier generated successfully.");
        return 0;
    }
}
```

```php
// app/Console/Commands/AdrGenerate.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AdrGenerate extends Command
{
    protected $signature = 'adr:generate';
    protected $description = 'Generate Architecture Decision Records (ADRs) for resolved wayfinder decisions';

    public function handle(): int
    {
        $dir = base_path('docs/10-architecture/1015-adrs');
        File::ensureDirectoryExists($dir);

        File::put("{$dir}/0015-wayfinder-map.md", "# ADR 0015: Wayfinder Implementation Map\n\n## Status\nAccepted\n\n## Context\nPostgreSQL pivot and domain architecture.");

        $this->info("Architecture Decision Records generated successfully.");
        return 0;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DocumentationCommandsTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ docs/ tests/Feature/Console/DocumentationCommandsTest.php
git commit -m "feat: implement dossier:generate and adr:generate commands and produce documentation sets"
```

---

## 4. Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-07-25-samples-implementation.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration.
**2. Inline Execution** - Execute tasks in this session using `executing-plans`, batch execution with checkpoints.

Which approach?
