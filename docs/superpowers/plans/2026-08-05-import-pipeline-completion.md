# Import Pipeline Completion — Full Mapping on the Reverted Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the import pipeline so it loads upstream sample datasets (Chinook, Northwind, Pagila) into the app's UUID domain tables via the shadow-schema swap, with full-fidelity mapping, normalized Pagila addresses, proper binary blob storage, and complete search projections per Decision #31 — all through Eloquent, never raw `DB::table()->insert()`.

**Architecture:** Build on the approved deviation analysis (`docs/superpowers/specs/2026-08-05-import-deviation-analysis.md`), which reverted both Map #15 deviations. Three PostgreSQL schemas per product: `<product>_source` (upstream dump, scratch), `<product>_staging` (app-shape, UUID + triggers, transform target via Eloquent staging-model subclasses), `<product>` (live, atomic swap target). The shadow-swap (#28/#41/ADR 100308) is restored; the cross-schema `product_portfolio_snapshots` view is recreated post-swap; embeddings are batch-queued in a rebuild phase (#28 Decision 15). Search-projection triggers are rewritten to use `TG_TABLE_SCHEMA` (fixing a swap-isolation bug) and implement the full #31 field→weight mapping.

**Tech Stack:** Laravel 13, PHP 8.5, PostgreSQL (multi-schema: `public`, `chinook`, `northwind`, `pagila`, `*_source`, `*_staging`), Pest 5, Spatie Permission, pgvector, Eloquent.

## Global Constraints

- PHP 8.5 constructor property promotion: `public function __construct(public string $product) {}`
- Explicit return type declarations on all methods: `public function load(string $schema): void`
- Curly braces for all control structures, even single-line bodies
- TitleCase for Enum keys (existing `SamplesProduct` enum: `Chinook`, `Northwind`, `Pagila`)
- Use `mb_*` string functions where text processing is involved
- Prefer PHPDoc blocks over inline comments; inline comments only for exceptionally complex logic
- Follow existing code conventions in neighboring files (see `app/Services/ProductImport/`)
- Run `vendor/bin/pint --dirty --format agent` after all PHP changes (NOT `--test`)
- Create classes via `php artisan make:class ... --no-interaction`
- Tests via `php artisan make:test --pest ... --no-interaction`
- Run tests: `php artisan test --compact` (filter with `--filter=name`)
- Do NOT delete tests without approval
- Do NOT change application dependencies without approval
- **Eloquent is the sole write path to product-domain tables.** Never use `DB::table()->insert()` for domain rows. The `SourceIdentityRegistry` (writes to `public.source_identities`, no domain trait) is the one exception.
- This repo uses **git, not jj**. Commit to `main`; no branch unless asked.

---

## Critical Design Constraints (read before implementing)

1. **Search-projection trigger functions are currently hardcoded to `<product>.`** in their bodies (e.g. `INSERT INTO chinook.search_projections …`). They MUST be rewritten to use `TG_TABLE_SCHEMA` (Phase 0, Task 0.3) before the shadow-swap can work — otherwise staging writes hit the live schema, breaking swap isolation. This is non-negotiable and lands first.
2. **`BelongsToProductDomain` gates by product, not schema.** `getProductDomain()` returns a hardcoded `SamplesProduct` enum; `ResetWindow::assertWritable()` throws during any active `ResetRun`. So Eloquent writes through domain models are blocked during import — UNLESS the write goes through a staging-model subclass that does NOT use the trait. Phase 1 builds those subclasses. This makes #29 Decision 2's stated staging exemption real.
3. **`Tier1SourceObserver`'s `is_staging` guard is dead code** — `is_staging` is bound nowhere. Phase 1 wires it (`app()->instance('is_staging', true)` around staging writes) so the observer no-ops during staging instead of dispatching per-row `EmbeddingJob`s (#28 Decision 15).
4. **`SourceIdentityRegistry` is safe during a run** — `SourceIdentity` uses only `HasUuids`, no domain trait. Confirmed: it writes to `public.source_identities` via Eloquent, which is not domain-gated.
5. **Pagila has a circular FK** (`staff.store_id` ↔ `stores.manager_staff_id`), both `DEFERRABLE INITIALLY DEFERRED`. Inserts for staff+stores MUST happen inside one transaction, adjacent in ordering.
6. **Self-referential FKs** (Chinook `employees.reports_to`, Northwind `employees.reports_to`) are NOT deferrable. Use two-pass: insert with `reports_to = null`, then `UPDATE` to set the resolved value.
7. **No `product`/`product_domain` column exists** on any domain table. Domain membership is code-level (`getProductDomain()` returns a hardcoded enum). Do NOT write a product column.
8. **`reset_runs` has NO CHECK constraints** on status/kind/phase (convention only). The `evidence` column is bare `jsonb`; the `ResetEvidence` value object exists at `app/Services/ProductReset/ResetEvidence.php` but is not yet wired into the pipeline.
9. **`PostgresSourceReader::executeSqlDump` does a blind global `str_replace('public.', "{$schema}.")`** — no escaping, rewrites inside string literals. Acceptable for the controlled fixture path; the real upstream dumps need verification at Phase 5.

---

## File Structure

### Files to Create

| File                                                                                    | Responsibility                                                                                             |
| --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `database/migrations/pagila/2026_07_24_212002_create_pagila_addresses_and_relink.php`   | `pagila.addresses` table; rewire staff/customer/store to `address_id` FK                                   |
| `database/migrations/2026_08_05_000001_change_blob_columns_to_bytea.php`                | `northwind.categories.picture`, `northwind.employees.photo`, `pagila.staff.picture` → `bytea`              |
| `database/migrations/chinook/2026_07_24_210002_rewrite_chinook_search_triggers.php`     | Drop old triggers/functions; recreate generic `TG_TABLE_SCHEMA` function + per-entity triggers per #31     |
| `database/migrations/northwind/2026_07_24_211002_rewrite_northwind_search_triggers.php` | Same for northwind                                                                                         |
| `database/migrations/pagila/2026_07_24_212003_rewrite_pagila_search_triggers.php`       | Same for pagila                                                                                            |
| `app/Services/ProductImport/StagingSchemaBuilder.php`                                   | Clones live `<product>` schema structure into `<product>_staging` (tables, FKs, triggers, indexes — empty) |
| `app/Services/ProductImport/PortfolioViewRecreator.php`                                 | Re-runs the `CREATE OR REPLACE VIEW public.product_portfolio_snapshots` post-swap                          |
| `app/Console/Commands/ProductStage.php`                                                 | `product:stage {product}` — thin wrapper over `StagingSchemaBuilder`                                       |
| `app/Domain/Staging/Chinook/{Artist,Album,...}.php`                                     | Staging model subclasses (11) — extend domain models, override table, NO trait                             |
| `app/Domain/Staging/Northwind/{Category,Customer,...}.php`                              | Staging model subclasses (11)                                                                              |
| `app/Domain/Staging/Pagila/{Address,Actor,...}.php`                                     | Staging model subclasses (16, incl. Address)                                                               |
| `app/Services/ProductImport/Mapping/TableMapper.php`                                    | Abstract per-table mapper (Eloquent write via staging subclass)                                            |
| `app/Services/ProductImport/Mapping/SelfReferentialMapper.php`                          | Two-pass self-FK resolution                                                                                |
| `app/Services/ProductImport/Mapping/ProductMapper.php`                                  | Orchestrator: truncate staging + run mappers in tx                                                         |
| `app/Services/ProductImport/Mapping/Chinook/ChinookProductMapper.php` + 11 mappers      | Chinook concrete mappers                                                                                   |
| `app/Services/ProductImport/Mapping/Northwind/NorthwindProductMapper.php` + 11 mappers  | Northwind concrete mappers                                                                                 |
| `app/Services/ProductImport/Mapping/Pagila/PagilaProductMapper.php` + 16 mappers        | Pagila concrete mappers (incl. AddressMapper)                                                              |
| `app/Services/ProductImport/EmbeddingDrain.php`                                         | Post-publish rebuild phase: dispatch `EmbeddingJob` per pending row, wait for drain                        |
| `tests/Feature/Import/BehavioralComplianceTest.php`                                     | #81 standing compliance test (red→green)                                                                   |
| `tests/Feature/Import/TransformChinookTest.php`                                         | Chinook end-to-end transform                                                                               |
| `tests/Feature/Import/TransformNorthwindTest.php`                                       | Northwind transform (string PKs, bytea blobs)                                                              |
| `tests/Feature/Import/TransformPagilaTest.php`                                          | Pagila transform (addresses, circular FK)                                                                  |
| `tests/Feature/Import/SearchProjectionMappingTest.php`                                  | Assert every tier-1/tier-2 entity has projection row with #31-correct weights                              |
| `tests/Feature/Import/SchemaPreservationTest.php`                                       | View recreated post-swap                                                                                   |
| `tests/Fixtures/Sources/chinook/minimal.sql`                                            | Minimal Chinook dump                                                                                       |
| `tests/Fixtures/Sources/northwind/minimal.sql`                                          | Minimal Northwind dump (incl. bytea picture/photo)                                                         |
| `tests/Fixtures/Sources/pagila/schema-minimal.sql` + `data-minimal.sql`                 | Minimal Pagila dumps (incl. normalized address)                                                            |

### Files to Modify

| File                                                                                | Change                                                                                                                |
| ----------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php` | Ensure `CREATE OR REPLACE VIEW`; extract DDL to a reusable location for `PortfolioViewRecreator`                      |
| `app/Observers/Tier1SourceObserver.php`                                             | (No change — the `is_staging` guard is already correct; it just needs binding, done in the importer)                  |
| `app/Services/ProductImport/ChinookImporter.php`                                    | Restore shadow-swap; call `StagingSchemaBuilder` + `ChinookProductMapper` + `EmbeddingDrain`; recreate view post-swap |
| `app/Services/ProductImport/NorthwindImporter.php`                                  | Same — `NorthwindProductMapper`                                                                                       |
| `app/Services/ProductImport/PagilaImporter.php`                                     | Same — `PagilaProductMapper`                                                                                          |
| `app/Services/ProductImport/ProductImportPipeline.php`                              | Wire `ResetEvidence` into the evidence write                                                                          |
| `app/Models/Chinook/*.php` (12 files)                                               | Add missing `$casts` (decimals, timestamps, integers); delete phantom `Chinook.php`                                   |
| `app/Models/Northwind/Northwind.php`                                                | Delete (phantom, references non-existent table)                                                                       |
| `app/Models/Pagila/{Staff,Customer,Store}.php`                                      | Update: drop `address` cast/relation, add `addressId` relation to `Address`                                           |
| `app/Models/Pagila/Address.php`                                                     | New model for `pagila.addresses`                                                                                      |

---

## Task Index

- **Phase 0 — Schema corrections** (Tasks 0.1–0.5): migrations + model fixes; transform writes against corrected schema
- **Phase 1 — Transform infrastructure** (Tasks 1.1–1.3): staging subclasses, `StagingSchemaBuilder`, `is_staging` wiring
- **Phase 2 — Mapping layer** (Tasks 2.1–2.4): Eloquent `TableMapper`, per-product mappers (corrected from audit)
- **Phase 3 — Importer rewrite** (Tasks 3.1–3.3): three-schema flow, shadow-swap, rebuild/embedding-drain
- **Phase 4 — Tests** (Tasks 4.1–4.4): #81 compliance, fixtures, transform tests, trigger-coverage
- **Phase 5 — Gates** (Task 5.1): full suite, Pint, real import

---

## Phase 0 — Schema corrections

### Task 0.1: Pagila addresses table + FK graph

**Files:**

- Create: `database/migrations/pagila/2026_07_24_212002_create_pagila_addresses_and_relink.php`
- Create: `app/Models/Pagila/Address.php`
- Modify: `app/Models/Pagila/Staff.php`, `app/Models/Pagila/Customer.php`, `app/Models/Pagila/Store.php`
- Test: `tests/Feature/Import/PagilaAddressesSchemaTest.php`

**Interfaces:**

- Consumes: existing `pagila.cities`, `pagila.staff`, `pagila.customers`, `pagila.stores` tables
- Produces: `pagila.addresses` table (id uuid PK, address text NN, address2 text, district text, city_id uuid FK→cities, postal_code text, phone text, timestamps); `staff.address_id`/`customers.address_id`/`stores.address_id` uuid FK→addresses (replacing the flat `address` text column)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates pagila.addresses with the expected columns and FK to cities', function () {
    expect(Schema::hasTable('pagila.addresses'))->toBeTrue();

    expect(Schema::hasColumns('pagila.addresses', [
        'id', 'address', 'address2', 'district', 'city_id', 'postal_code', 'phone', 'created_at', 'updated_at',
    ]))->toBeTrue();

    $fks = DB::select("
        SELECT conname FROM pg_constraint
        WHERE conrelid = 'pagila.addresses'::regclass AND contype = 'f'
    ");
    expect(collect($fks)->pluck('conname')->contains(fn ($n) => str_contains($n, 'city_id')))->toBeTrue();
});

it('relinks staff, customers, stores to address_id FK and drops the flat address column', function () {
    foreach (['staff', 'customers', 'stores'] as $table) {
        expect(Schema::hasColumn("pagila.{$table}", 'address_id'))->toBeTrue("pagila.{$table} should have address_id");
        expect(Schema::hasColumn("pagila.{$table}", 'address'))->toBeFalse("pagila.{$table} should NOT have flat address column");
    }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PagilaAddressesSchema`
Expected: FAIL — `pagila.addresses` table does not exist.

- [ ] **Step 3: Create the migration**

```bash
php artisan make:migration create_pagila_addresses_and_relink --path=database/migrations/pagila --no-interaction
```

Rename the generated file to `2026_07_24_212002_create_pagila_addresses_and_relink.php`. Implement:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagila.addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('address');
            $table->text('address2')->nullable();
            $table->text('district')->nullable();
            $table->uuid('city_id');
            $table->foreign('city_id')->references('id')->on('pagila.cities')->cascadeOnDelete();
            $table->text('postal_code')->nullable();
            $table->text('phone')->nullable();
            $table->timestamps();
        });

        foreach (['staff', 'customers', 'stores'] as $table) {
            Schema::table("pagila.{$table}", function (Blueprint $t) {
                $t->dropColumn('address');
                $t->uuid('address_id')->nullable()->after('id');
                $t->foreign('address_id')->references('id')->on('pagila.addresses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['staff', 'customers', 'stores'] as $table) {
            Schema::table("pagila.{$table}", function (Blueprint $t) {
                $t->dropForeign(["{$table}_address_id_foreign"]);
                $t->dropColumn('address_id');
                $t->text('address')->nullable()->after('id');
            });
        }

        Schema::dropIfExists('pagila.addresses');
    }
};
```

- [ ] **Step 4: Create the `Address` model**

```php
<?php

declare(strict_types=1);

namespace App\Models\Pagila;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Eloquent\Attributes\Table;

#[Table('pagila.addresses')]
final class Address extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Pagila;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'address_id');
    }
}
```

- [ ] **Step 5: Update `Staff`, `Customer`, `Store` models**

In each of `app/Models/Pagila/Staff.php`, `app/Models/Pagila/Customer.php`, `app/Models/Pagila/Store.php`: remove any `$casts` entry or relation referencing the old `address` string column; add:

```php
public function address(): BelongsTo
{
    return $this->belongsTo(Address::class, 'address_id');
}
```

(Add the `use Illuminate\Database\Eloquent\Relations\BelongsTo;` import if absent.)

- [ ] **Step 6: Run the test**

Run: `php artisan test --compact --filter=PagilaAddressesSchema`
Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/pagila/2026_07_24_212002_create_pagila_addresses_and_relink.php \
        app/Models/Pagila/Address.php app/Models/Pagila/Staff.php app/Models/Pagila/Customer.php app/Models/Pagila/Store.php \
        tests/Feature/Import/PagilaAddressesSchemaTest.php
git commit -m "feat(pagila): add normalized addresses table, relink staff/customer/store via address_id FK"
```

### Task 0.2: Binary blob columns → bytea

**Files:**

- Create: `database/migrations/2026_08_05_000001_change_blob_columns_to_bytea.php`
- Test: `tests/Feature/Import/BlobColumnsByteaTest.php`

**Interfaces:**

- Consumes: existing `northwind.categories.picture` (text), `northwind.employees.photo` (text), `pagila.staff.picture` (text)
- Produces: those three columns as `bytea`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('stores northwind.categories.picture as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        WHERE c.relname = 'categories' AND a.attname = 'picture'
    ")->type;

    expect($type)->toBe('bytea');
});

it('stores northwind.employees.photo as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        WHERE c.relname = 'employees' AND a.attname = 'photo'
    ")->type;

    expect($type)->toBe('bytea');
});

it('stores pagila.staff.picture as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        WHERE c.relname = 'staff' AND a.attname = 'picture'
    ")->type;

    expect($type)->toBe('bytea');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BlobColumnsBytea`
Expected: FAIL — columns are `text`, not `bytea`.

- [ ] **Step 3: Create the migration**

```bash
php artisan make:migration change_blob_columns_to_bytea --no-interaction
```

Rename to `2026_08_05_000001_change_blob_columns_to_bytea.php`. Implement:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE northwind.categories ALTER COLUMN picture TYPE bytea USING picture::bytea;');
        DB::statement('ALTER TABLE northwind.employees ALTER COLUMN photo TYPE bytea USING photo::bytea;');
        DB::statement('ALTER TABLE pagila.staff ALTER COLUMN picture TYPE bytea USING picture::bytea;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE northwind.categories ALTER COLUMN picture TYPE text USING picture::text;');
        DB::statement('ALTER TABLE northwind.employees ALTER COLUMN photo TYPE text USING photo::text;');
        DB::statement('ALTER TABLE pagila.staff ALTER COLUMN picture TYPE text USING picture::text;');
    }
};
```

- [ ] **Step 4: Run the test**

Run: `php artisan test --compact --filter=BlobColumnsBytea`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_05_000001_change_blob_columns_to_bytea.php tests/Feature/Import/BlobColumnsByteaTest.php
git commit -m "feat: change blob columns (picture/photo) from text to bytea for proper binary storage"
```

### Task 0.3: Rewrite search-projection triggers with TG_TABLE_SCHEMA + #31 mapping

This is the largest schema task. It fixes the swap-isolation bug (triggers hardcoded to `chinook.`) and implements the operator's "complete search projections" decision (#31 field→weight mapping + gap triggers).

**Files:**

- Create: `database/migrations/chinook/2026_07_24_210002_rewrite_chinook_search_triggers.php`
- Create: `database/migrations/northwind/2026_07_24_211002_rewrite_northwind_search_triggers.php`
- Create: `database/migrations/pagila/2026_07_24_212003_rewrite_pagila_search_triggers.php`
- Test: `tests/Feature/Import/SearchTriggerSchemaTest.php`

**Interfaces:**

- Consumes: existing per-product `search_projections` tables + source entity tables
- Produces: one generic trigger function per product using `TG_TABLE_SCHEMA`; per-entity triggers for all tier-1/tier-2 entities per #31; `weight_b_text` now populated for tracks/products/films

**#31 tier-1/tier-2 coverage (must match exactly):**

- Chinook: artists(T1), albums(T1), tracks(T1, B=genre), playlists(T1), customers(T2), employees(T2), invoices(T2), genres(T2) — 8 triggers
- Northwind: products(T1, B=category), categories(T1), suppliers(T1), customers(T2), employees(T2), orders(T2), shippers(T2) — 7 triggers
- Pagila: films(T1, B=category+rating), actors(T1), categories(T1), languages(T1), customers(T2), staff(T2), rentals(T2), payments(T2), stores(T2) — 9 triggers

- [ ] **Step 1: Write the failing test (chinook)**

```php
<?php

declare(strict_types=1);

use App\Models\Chinook\Artist;
use App\Models\Chinook\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('chinook trigger writes weight_d from name and tier-1 pending state for artists', function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
    DB::statement('CREATE SCHEMA chinook_staging');

    // Clone just the artists + search_projections into staging for this isolated test
    DB::statement('CREATE TABLE chinook_staging.search_projections (LIKE chinook.search_projections INCLUDING ALL)');
    DB::statement('CREATE TABLE chinook_staging.artists (LIKE chinook.artists INCLUDING ALL)');

    // Create the generic trigger in staging pointing at staging
    // (In production this is done by StagingSchemaBuilder; here we simulate)
    $artist = new App\Models\Chinook\Artist;
    $artist->setTable('chinook_staging.artists');
    $artist->id = (string) \Illuminate\Support\Str::uuid7();
    $artist->name = 'AC/DC';
    $artist->save();

    $proj = DB::table('chinook_staging.search_projections')->where('id', $artist->id)->first();
    expect($proj)->not->toBeNull()
        ->and($proj->entity_type)->toBe('artist')
        ->and($proj->weight_d_text)->toBe('AC/DC')
        ->and($proj->embedding_state)->toBe('pending');
});
```

Note: this test requires the trigger to fire on the staging table. Because the trigger function uses `TG_TABLE_SCHEMA`, it targets `chinook_staging.search_projections`. Run it after the migration to confirm.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SearchTriggerSchema`
Expected: FAIL — current triggers reference hardcoded `chinook.` so the staging-table insert writes to `chinook.search_projections`, not staging.

- [ ] **Step 3: Create the chinook trigger-rewrite migration**

```bash
php artisan make:migration rewrite_chinook_search_triggers --path=database/migrations/chinook --no-interaction
```

Rename to `2026_07_24_210002_rewrite_chinook_search_triggers.php`. Implement (drop old, create generic function + per-entity triggers):

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old per-table functions and triggers
        $oldTables = ['artists', 'albums', 'tracks', 'customers', 'employees', 'playlists', 'genres', 'media_types', 'invoices'];
        foreach ($oldTables as $t) {
            DB::statement("DROP TRIGGER IF EXISTS trg_chinook_{$t}_search ON chinook.{$t}");
            DB::statement("DROP FUNCTION IF EXISTS chinook.sync_{$t}_search_projection()");
        }

        // Generic trigger function — uses TG_TABLE_SCHEMA for isolation
        // #31 mapping: D=title/name, C=desc, B=category, A=source_id (id::text)
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION chinook.sync_search_projection()
            RETURNS trigger AS $$
            DECLARE
                proj_schema text := TG_TABLE_SCHEMA;
                d_text text; c_text text; b_text text; a_text text;
                new_state text;
            BEGIN
                CASE TG_TABLE_NAME
                    -- Tier 1 (embedding_state = 'pending')
                    WHEN 'artists' THEN
                        d_text := NEW.name; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'pending';
                    WHEN 'albums' THEN
                        d_text := NEW.title; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'pending';
                    WHEN 'tracks' THEN
                        d_text := NEW.name; c_text := NEW.composer;
                        EXECUTE format('SELECT g.name FROM %I.genres g WHERE g.id = $1', proj_schema) INTO b_text USING NEW.genre_id;
                        a_text := NEW.id::text; new_state := 'pending';
                    WHEN 'playlists' THEN
                        d_text := NEW.name; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'pending';
                    -- Tier 2 (embedding_state = 'lexical_only')
                    WHEN 'customers' THEN
                        d_text := NEW.first_name || ' ' || NEW.last_name; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'lexical_only';
                    WHEN 'employees' THEN
                        d_text := NEW.first_name || ' ' || NEW.last_name || ' ' || COALESCE(NEW.title, '');
                        c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'lexical_only';
                    WHEN 'invoices' THEN
                        d_text := 'Invoice #' || NEW.id::text; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'lexical_only';
                    WHEN 'genres' THEN
                        d_text := NEW.name; c_text := NULL; b_text := NULL;
                        a_text := NEW.id::text; new_state := 'lexical_only';
                    ELSE
                        RETURN NEW;
                END CASE;

                IF TG_OP = 'DELETE' THEN
                    EXECUTE format('DELETE FROM %I.search_projections WHERE id = $1', proj_schema) USING OLD.id;
                    RETURN OLD;
                END IF;

                EXECUTE format(
                    'INSERT INTO %I.search_projections (id, entity_type, weight_d_text, weight_c_text, weight_b_text, weight_a_text, embedding_state, created_at, updated_at)
                     VALUES ($1, $2, $3, $4, $5, $6, $7, NOW(), NOW())
                     ON CONFLICT (id) DO UPDATE SET
                         entity_type = EXCLUDED.entity_type,
                         weight_d_text = EXCLUDED.weight_d_text,
                         weight_c_text = EXCLUDED.weight_c_text,
                         weight_b_text = EXCLUDED.weight_b_text,
                         weight_a_text = EXCLUDED.weight_a_text,
                         embedding_state = EXCLUDED.embedding_state,
                         updated_at = NOW()',
                    proj_schema
                ) USING NEW.id, TG_TABLE_NAME, d_text, c_text, b_text, a_text, new_state;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Per-entity triggers (tier-1 + tier-2 only; tier-3 tables get none)
        $triggerTables = ['artists', 'albums', 'tracks', 'playlists', 'customers', 'employees', 'invoices', 'genres'];
        foreach ($triggerTables as $t) {
            DB::statement("DROP TRIGGER IF EXISTS trg_chinook_{$t}_search ON chinook.{$t}");
            DB::statement("CREATE TRIGGER trg_chinook_{$t}_search AFTER INSERT OR UPDATE OR DELETE ON chinook.{$t} FOR EACH ROW EXECUTE FUNCTION chinook.sync_search_projection()");
        }
    }

    public function down(): void
    {
        $triggerTables = ['artists', 'albums', 'tracks', 'playlists', 'customers', 'employees', 'invoices', 'genres'];
        foreach ($triggerTables as $t) {
            DB::statement("DROP TRIGGER IF EXISTS trg_chinook_{$t}_search ON chinook.{$t}");
        }
        DB::statement('DROP FUNCTION IF EXISTS chinook.sync_search_projection()');
        // Note: restoring the old per-table functions is not practical; fresh migrate recreates them.
    }
};
```

- [ ] **Step 4: Create the northwind trigger-rewrite migration** (`2026_07_24_211002_rewrite_northwind_search_triggers.php`)

Same structure. Northwind #31 mapping:

- `products` (T1): d=`product_name`, c=`quantity_per_unit`, b=category name via `EXECUTE format('SELECT category_name FROM %I.categories WHERE id = $1', proj_schema) USING NEW.category_id`, a=`id::text`, pending
- `categories` (T1): d=`category_name`, c=`description`, b=NULL, a, pending
- `suppliers` (T1): d=`company_name`, c=NULL, b=NULL, a, pending
- `customers` (T2): d=`company_name || ' ' || COALESCE(contact_name, '')`, lexical_only
- `employees` (T2): d=`first_name || ' ' || last_name || ' ' || COALESCE(title, '')`, lexical_only
- `orders` (T2): d=`'Order #' || id::text`, lexical_only
- `shippers` (T2): d=`company_name`, lexical_only

Trigger tables: `['products', 'categories', 'suppliers', 'customers', 'employees', 'orders', 'shippers']`.

- [ ] **Step 5: Create the pagila trigger-rewrite migration** (`2026_07_24_212003_rewrite_pagila_search_triggers.php`)

Pagila #31 mapping:

- `films` (T1): d=`title`, c=`description`, b=category names via film_category join + rating:
    ```sql
    EXECUTE format(
        'SELECT COALESCE(string_agg(c.name, '', ''), '''') || COALESCE('', '' || $2, '''')
         FROM %I.categories c
         JOIN %I.film_categories fc ON fc.category_id = c.id
         WHERE fc.film_id = $1',
        proj_schema, proj_schema
    ) INTO b_text USING NEW.id, NEW.rating;
    ```
    (Handle NULL rating gracefully.)
- `actors` (T1): d=`first_name || ' ' || last_name`, pending
- `categories` (T1): d=`name`, pending
- `languages` (T1): d=`name`, pending
- `customers` (T2): d=`first_name || ' ' || last_name`, lexical_only
- `staff` (T2): d=`first_name || ' ' || last_name`, lexical_only
- `rentals` (T2): d=`'Rental #' || id::text`, lexical_only
- `payments` (T2): d=`'Payment #' || id::text`, lexical_only
- `stores` (T2): d=`'Store ' || id::text`, lexical_only

Trigger tables: `['films', 'actors', 'categories', 'languages', 'customers', 'staff', 'rentals', 'payments', 'stores']`.

- [ ] **Step 6: Run the test**

Run: `php artisan test --compact --filter=SearchTriggerSchema`
Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/chinook/2026_07_24_210002_rewrite_chinook_search_triggers.php \
        database/migrations/northwind/2026_07_24_211002_rewrite_northwind_search_triggers.php \
        database/migrations/pagila/2026_07_24_212003_rewrite_pagila_search_triggers.php \
        tests/Feature/Import/SearchTriggerSchemaTest.php
git commit -m "feat(search): rewrite triggers with TG_TABLE_SCHEMA + implement #31 field-weight mapping (B-weight live)"
```

### Task 0.4: Make portfolio view recreatable post-swap

**Files:**

- Modify: `database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php`
- Create: `app/Services/ProductImport/PortfolioViewRecreator.php`
- Test: `tests/Feature/Import/PortfolioViewRecreatorTest.php`

**Interfaces:**

- Consumes: the view DDL
- Produces: `PortfolioViewRecreator::recreate(): void` — re-runs `CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS …`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\PortfolioViewRecreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('recreates the portfolio view after it is dropped', function () {
    DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');

    $existsBefore = DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') IS NOT NULL AS exists")->exists;
    expect($existsBefore)->toBeFalse();

    app(PortfolioViewRecreator::class)->recreate();

    $existsAfter = DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') IS NOT NULL AS exists")->exists;
    expect($existsAfter)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PortfolioViewRecreator`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `PortfolioViewRecreator`**

Extract the view DDL from the `213000` migration into a single source of truth. Simplest: a `private const VIEW_SQL` on the recreator, and the migration references it. Implement:

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;

final class PortfolioViewRecreator
{
    public const VIEW_SQL = <<<'SQL'
        CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS
        SELECT 'chinook'::text AS product,
               jsonb_build_object(
                   'tables', (SELECT count(*) FROM information_schema.tables WHERE table_schema = 'chinook' AND table_type = 'BASE TABLE'),
                   'artists', (SELECT count(*) FROM chinook.artists),
                   'tracks', (SELECT count(*) FROM chinook.tracks)
               ) AS stats
        UNION ALL
        SELECT 'northwind'::text,
               jsonb_build_object(
                   'tables', (SELECT count(*) FROM information_schema.tables WHERE table_schema = 'northwind' AND table_type = 'BASE TABLE'),
                   'products', (SELECT count(*) FROM northwind.products),
                   'orders', (SELECT count(*) FROM northwind.orders)
               )
        UNION ALL
        SELECT 'pagila'::text,
               jsonb_build_object(
                   'tables', (SELECT count(*) FROM information_schema.tables WHERE table_schema = 'pagila' AND table_type = 'BASE TABLE'),
                   'films', (SELECT count(*) FROM pagila.films),
                   'actors', (SELECT count(*) FROM pagila.actors)
               );
    SQL;

    public function recreate(): void
    {
        DB::statement(self::VIEW_SQL);
    }
}
```

Note: read the actual `213000` migration file and copy its exact DDL into `VIEW_SQL` — the above is the shape; the real DDL must match. Then refactor `213000`'s `up()` to call `app(PortfolioViewRecreator::class)->recreate()` (or reference the constant) so there's one source of truth.

- [ ] **Step 4: Refactor the 213000 migration** to use the recreator's constant (avoid DRY violation):

```php
// in 213000 up()
app(\App\Services\ProductImport\PortfolioViewRecreator::class)->recreate();
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter=PortfolioViewRecreator`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/PortfolioViewRecreator.php \
        database/migrations/2026_07_24_213000_create_product_portfolio_snapshots_view.php \
        tests/Feature/Import/PortfolioViewRecreatorTest.php
git commit -m "feat(import): extract portfolio view DDL into recreator for post-swap recreate"
```

### Task 0.5: Model corrections

**Files:**

- Delete: `app/Models/Chinook/Chinook.php`, `app/Models/Northwind/Northwind.php` (phantom models referencing non-existent tables)
- Modify: all 12 `app/Models/Chinook/*.php` files — add missing `$casts`
- Test: `tests/Unit/ChinookModelCastsTest.php`

**Interfaces:**

- Consumes: nothing
- Produces: corrected models with proper casts; no phantom models

- [ ] **Step 1: Delete phantom models**

```bash
git rm app/Models/Chinook/Chinook.php app/Models/Northwind/Northwind.php
```

- [ ] **Step 2: Add `$casts` to Chinook models**

Add to `app/Models/Chinook/Track.php`:

```php
protected $casts = [
    'unit_price' => 'decimal:2',
    'milliseconds' => 'integer',
    'bytes' => 'integer',
];
```

Add to `app/Models/Chinook/Invoice.php`:

```php
protected $casts = [
    'total' => 'decimal:2',
    'invoice_date' => 'datetime',
];
```

Add to `app/Models/Chinook/InvoiceLine.php`:

```php
protected $casts = [
    'unit_price' => 'decimal:2',
    'quantity' => 'integer',
];
```

Add to `app/Models/Chinook/Employee.php`:

```php
protected $casts = [
    'birth_date' => 'datetime',
    'hire_date' => 'datetime',
];
```

- [ ] **Step 3: Write the test**

```php
<?php

declare(strict_types=1);

use App\Models\Chinook\Track;
use App\Models\Chinook\Invoice;
use App\Models\Chinook\InvoiceLine;

uses();

it('casts chinook money columns to decimal:2', function () {
    expect((new Track)->getCasts()['unit_price'] ?? null)->toBe('decimal:2')
        ->and((new Invoice)->getCasts()['total'] ?? null)->toBe('decimal:2')
        ->and((new InvoiceLine)->getCasts()['unit_price'] ?? null)->toBe('decimal:2');
});

it('does not have phantom Chinook\Chinook model', function () {
    expect(class_exists('App\Models\Chinook\Chinook'))->toBeFalse();
});
```

- [ ] **Step 4: Run the test**

Run: `php artisan test --compact --filter=ChinookModelCasts`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Chinook/ tests/Unit/ChinookModelCastsTest.php
git commit -m "fix(models): delete phantom product-root models, add missing casts to Chinook models"
```

---

## Phase 1 — Transform infrastructure

### Task 1.1: Staging model subclasses

**Files:**

- Create: `app/Domain/Staging/Chinook/{Artist,Album,Genre,MediaType,Employee,Customer,Track,Playlist,PlaylistTrack,Invoice,InvoiceLine}.php` (11 files)
- Create: `app/Domain/Staging/Northwind/{Category,Customer,Employee,Region,Territory,EmployeeTerritory,Shipper,Supplier,Product,Order,OrderDetail}.php` (11 files)
- Create: `app/Domain/Staging/Pagila/{Address,Country,City,Language,Actor,Category,Staff,Store,Customer,Film,FilmActor,FilmCategory,FilmText,Inventory,Rental,Payment}.php` (16 files)
- Test: `tests/Unit/Staging/StagingModelTraitTest.php`

**Interfaces:**

- Consumes: existing domain models under `App\Models\<Product>\`
- Produces: staging subclasses that bind to `<product>_staging.*` and do NOT use `BelongsToProductDomain`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Domain\Staging\Chinook\Artist as StagingArtist;
use App\Models\Chinook\Artist;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('staging artist subclass targets the staging schema table', function () {
    $staging = new StagingArtist;
    expect($staging->getTable())->toBe('chinook_staging.artists');
});

it('staging artist subclass does NOT use the BelongsToProductDomain trait', function () {
    expect(in_array('App\Traits\BelongsToProductDomain', class_uses(StagingArtist::class) ?: [], true))->toBeFalse();
});

it('staging artist subclass extends the live domain model', function () {
    expect((new StagingArtist) instanceof Artist)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=StagingModelTrait`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the Chinook staging subclasses**

Pattern (repeat for all 11, adjusting class name + table):

```php
<?php

declare(strict_types=1);

namespace App\Domain\Staging\Chinook;

use App\Models\Chinook\Artist;
use Illuminate\Database\Eloquent\Model;
use Laravel\Eloquent\Attributes\Table;

#[Table('chinook_staging.artists')]
final class Artist extends \App\Models\Chinook\Artist
{
    // Override: do NOT boot BelongsToProductDomain.
    // The parent trait's bootBelongsToProductDomain() registers hooks via static::creating(...).
    // The cleanest override is to redeclare the trait-less boot and skip the parent hook.

    public static function bootBelongsToProductDomain(): void
    {
        // Intentionally empty — staging writes are exempt from the write-block (#29 Decision 2).
    }
}
```

Note: because PHP traits' static `boot*` methods are called automatically by Eloquent's boot loop, simply extending the parent would still trigger `bootBelongsToProductDomain`. Overriding it as a no-op in the subclass is the correct, minimal way to neutralize it. Verify this works in the test; if PHP's boot loop calls the parent's boot anyway, the alternative is to NOT extend and instead re-declare the model fresh (heavier). Prefer the override; confirm at Step 4.

Repeat for the remaining 10 Chinook subclasses, 11 Northwind, 16 Pagila. Each sets `#[Table('<product>_staging.<table>')]` and overrides `bootBelongsToProductDomain()` to no-op.

- [ ] **Step 4: Run the test**

Run: `php artisan test --compact --filter=StagingModelTrait`
Expected: PASS. If FAIL (parent boot still fires), switch each subclass to a fresh model declaration (copy parent's `$fillable`/`$guarded`/relations, set table, no trait). Document the chosen approach in a PHPDoc.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Staging/ tests/Unit/Staging/StagingModelTraitTest.php
git commit -m "feat(import): add staging model subclasses that exempt staging writes from BelongsToProductDomain (#29 D2)"
```

### Task 1.2: StagingSchemaBuilder + product:stage command

**Files:**

- Create: `app/Services/ProductImport/StagingSchemaBuilder.php`
- Create: `app/Console/Commands/ProductStage.php`
- Test: `tests/Feature/Import/StagingSchemaBuilderTest.php`

**Interfaces:**

- Consumes: live `<product>` schema (tables, FKs, triggers, indexes)
- Produces: `StagingSchemaBuilder::build(string $product): void` — creates `<product>_staging` as a structural clone of `<product>` (empty); `product:stage {product}` Artisan command

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('builds chinook_staging as an empty structural clone of chinook', function () {
    app(StagingSchemaBuilder::class)->build('chinook');

    $tables = DB::select("
        SELECT table_name FROM information_schema.tables
        WHERE table_schema = 'chinook_staging' AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ");
    $names = array_column($tables, 'table_name');

    expect($names)->toContain('artists')
        ->and($names)->toContain('search_projections')
        ->and(DB::table('chinook_staging.artists')->count())->toBe(0);
});

it('staging trigger fires on staging insert and targets staging search_projections', function () {
    app(StagingSchemaBuilder::class)->build('chinook');

    DB::table('chinook_staging.artists')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'name' => 'Test Artist',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('chinook_staging.search_projections')->count())->toBe(1)
        ->and(DB::table('chinook.search_projections')->count())->toBe(0); // live untouched
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=StagingSchemaBuilder`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `StagingSchemaBuilder`**

Use `pg_dump --schema-only` shell-out (simplest, Postgres-native, handles all object types):

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

final class StagingSchemaBuilder
{
    /**
     * Build <product>_staging as an empty structural clone of the live <product> schema.
     *
     * Uses pg_dump --schema-only, rewrites the schema name, and reloads.
     * The generic search-projection trigger function uses TG_TABLE_SCHEMA,
     * so triggers in staging correctly target <product>_staging.search_projections.
     */
    public function build(string $product): void
    {
        $staging = "{$product}_staging";

        DB::statement("DROP SCHEMA IF EXISTS {$staging} CASCADE");
        DB::statement("CREATE SCHEMA {$staging}");

        $conn = config('database.connections.pgsql');
        $dsn = sprintf('host=%s port=%s dbname=%s user=%s',
            $conn['host'] ?? '127.0.0.1',
            $conn['port'] ?? '5432',
            $conn['database'],
            $conn['username'],
        );

        $dump = $this->dumpSchema($product, $dsn, $conn['password'] ?? '');
        $rewritten = str_replace("{$product}.", "{$staging}.", $dump);

        foreach ($this->splitStatements($rewritten) as $stmt) {
            if (mb_trim($stmt) !== '') {
                DB::statement($stmt);
            }
        }
    }

    private function dumpSchema(string $schema, string $dsn, string $password): string
    {
        $env = ['PGPASSWORD' => $password] + $_ENV;
        $process = new Process(
            ['pg_dump', "--schema={$schema}", '--schema-only', '--no-owner', '--no-privileges', "dbname={$dsn}"],
            null, $env,
        );
        $process->mustRun();

        return $process->getOutput();
    }

    /**
     * Split a dump into runnable statements, stripping psql meta-commands and comments.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        // Strip psql backslash commands and comment lines
        $sql = preg_replace('/^\\\\.*$/m', '', $sql);
        $sql = preg_replace('/^--.*$/m', '', $sql);

        // Split on semicolons followed by newlines. Function bodies ($$...$$) are preserved
        // because the $$ delimiter contains the internal semicolons safely when using
        // pg_dump's default output.
        $parts = preg_split('/;\s*\n/', $sql) ?: [];

        return array_map(fn ($p) => mb_trim($p).';', array_filter($parts, fn ($p) => mb_trim($p) !== ''));
    }
}
```

**Important note on `$$`-delimited function bodies:** pg_dump wraps PL/pgSQL function bodies in `$$` delimiters, and the internal semicolons are inside those delimiters. The naive `/;\s*\n/` split will break function bodies. The correct approach is to use `pg_dump --schema-only` output directly via `psgql` (pipe through `psql`), OR use `DB::unprepared($rewritten)` to execute the whole dump as one statement (Laravel's unprepared runs the entire string). **Prefer `DB::unprepared($rewritten)`** — change `splitStatements` loop to:

```php
DB::unprepared($rewritten);
```

This is simpler and correctly handles function bodies. Update the implementation accordingly. (The `splitStatements` private method can be removed.)

- [ ] **Step 4: Implement the `product:stage` command**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Console\Command;

class ProductStage extends Command
{
    protected $signature = 'product:stage {product : chinook|northwind|pagila}';

    protected $description = 'Build the <product>_staging schema as an empty structural clone of the live schema';

    public function handle(StagingSchemaBuilder $builder): int
    {
        $product = mb_strtolower((string) $this->argument('product'));

        if (! in_array($product, ['chinook', 'northwind', 'pagila'], true)) {
            $this->error("Unknown product: {$product}");

            return self::FAILURE;
        }

        $builder->build($product);
        $this->info("Built {$product}_staging schema.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter=StagingSchemaBuilder`
Expected: PASS. (Requires `pg_dump` on PATH — Herd provides it. If the test environment lacks `pg_dump`, fall back to the catalog-introspection approach: query `pg_class`/`pg_constraint`/`pg_index`/`pg_trigger` and re-issue DDL. Document the fallback in a PHPDoc.)

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/StagingSchemaBuilder.php app/Console/Commands/ProductStage.php \
        tests/Feature/Import/StagingSchemaBuilderTest.php
git commit -m "feat(import): add StagingSchemaBuilder + product:stage command for shadow-swap staging"
```

### Task 1.3: Wire is_staging flag (verified, not a standalone task)

The `is_staging` flag is wired inside the importer's staging-build scope (Phase 3, Task 3.1). There is no standalone class — it's a two-line bind/forget around the transform. Verify the guard works with a focused test here, then apply it in Task 3.1.

- [ ] **Step 1: Write the verification test**

```php
<?php

declare(strict_types=1);

use App\Observers\Tier1SourceObserver;
use App\Jobs\EmbeddingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;

uses(RefreshDatabase::class);

it('Tier1SourceObserver suppresses dispatch when is_staging is bound true', function () {
    app()->instance('is_staging', true);

    Queue::fake();

    $stub = new class extends Model
    {
        protected $table = 'chinook.artists';

        public function getProductDomain()
        {
            return \App\Enums\SamplesProduct::Chinook;
        }
    };

    app(Tier1SourceObserver::class)->saved($stub);

    Queue::assertNotPushed(EmbeddingJob::class);

    app()->forgetInstance('is_staging');
});

it('Tier1SourceObserver dispatches when is_staging is NOT bound', function () {
    Queue::fake();

    $stub = new class extends Model
    {
        protected $table = 'chinook.artists';

        public function getProductDomain()
        {
            return \App\Enums\SamplesProduct::Chinook;
        }
    };

    app(Tier1SourceObserver::class)->saved($stub);

    Queue::assertPushed(EmbeddingJob::class);
});
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --compact --filter=Tier1SourceObserver`
Expected: PASS — the guard code already exists; this just verifies it.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Import/Tier1SourceObserverStagingTest.php
git commit -m "test(import): verify Tier1SourceObserver is_staging suppression guard"
```

---

## Phase 2 — Mapping layer

### Task 2.1: TableMapper abstract base (Eloquent write)

**Files:**

- Create: `app/Services/ProductImport/Mapping/TableMapper.php`
- Test: `tests/Unit/Mapping/TableMapperTest.php`

**Interfaces:**

- Consumes: `SourceIdentityRegistry::getOrMint(string $entity, array $sourceKey): string`; a staging model class name (declared by subclass)
- Produces: `abstract class TableMapper` with `load(string $sourceSchema): int` that reads source rows, maps to staging-model instances, saves via Eloquent

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

uses(RefreshDatabase::class);

it('maps source rows to staging-model rows with resolved UUIDs and saves via Eloquent', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_source');
    DB::statement('CREATE TABLE test_source.artist (artist_id int, name text)');
    DB::statement("INSERT INTO test_source.artist VALUES (1, 'AC/DC'), (2, 'Metallica')");

    DB::statement('CREATE SCHEMA IF NOT EXISTS test_staging');
    Schema::create('test_staging.artists', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->with('test.artists', ['artist_id' => 1])->andReturn('aaaa-0001');
    $registry->shouldReceive('getOrMint')->with('test.artists', ['artist_id' => 2])->andReturn('aaaa-0002');

    // Test stub model bound to test_staging.artists
    $modelClass = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'test_staging.artists';
        protected $guarded = [];
        public $incrementing = false;
        protected $keyType = 'string';
    };
    $modelClass = get_class($modelClass);

    $mapper = new class($registry, $modelClass) extends TableMapper
    {
        public function __construct(SourceIdentityRegistry $r, private string $modelClass)
        {
            parent::__construct($r);
        }

        protected function entity(): string { return 'test.artists'; }
        protected function sourceSchemaTable(): string { return 'test_source.artist'; }
        protected function stagingModelClass(): string { return $this->modelClass; }
        protected function sourceKey(): array { return ['artist_id']; }
        protected function columns(): array { return ['name' => 'name']; }
    };

    $count = $mapper->load('test_source');

    expect($count)->toBe(2)
        ->and(DB::table('test_staging.artists')->count())->toBe(2)
        ->and(DB::table('test_staging.artists')->where('name', 'AC/DC')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TableMapperTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `TableMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Maps rows from an upstream source table into a staging-schema model via Eloquent.
 *
 * Each row's source primary key is translated to a stable UUIDv7 via
 * SourceIdentityRegistry, and foreign keys are resolved the same way.
 * Writes go through the staging-model subclass (no BelongsToProductDomain),
 * so they are exempt from the ResetWindow write-block (#29 Decision 2).
 */
abstract class TableMapper
{
    public function __construct(
        protected SourceIdentityRegistry $registry,
    ) {}

    /** Upstream identity, e.g. "chinook.artists". Must match the source_identities.entity CHECK. */
    abstract protected function entity(): string;

    /** Fully-qualified source table to read from, e.g. "chinook_source.artist". */
    abstract protected function sourceSchemaTable(): string;

    /** The staging-model subclass to instantiate and save, e.g. App\Domain\Staging\Chinook\Artist::class. */
    abstract protected function stagingModelClass(): string;

    /** Source columns forming the upstream PK, e.g. ["artist_id"]. */
    abstract protected function sourceKey(): array;

    /** Direct column maps: source column => staging-model attribute. */
    protected function columns(): array
    {
        return [];
    }

    /** FK maps: attribute => ['entity' => string, 'source' => string]. */
    protected function foreignKeys(): array
    {
        return [];
    }

    /** Static defaults applied to every row. */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Hook to transform or augment the attribute array before model instantiation.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $sourceSchema
     * @return array<int, array<string, mixed>>
     */
    protected function beforeInsert(array $rows, string $sourceSchema): array
    {
        return $rows;
    }

    /**
     * Read source rows, map to staging-model attributes, save via Eloquent. Returns rows saved.
     */
    public function load(string $sourceSchema): int
    {
        $rows = DB::table($this->sourceSchemaTable())->get()->all();
        $mapped = [];

        foreach ($rows as $row) {
            $sourceKey = [];
            foreach ($this->sourceKey() as $col) {
                $sourceKey[$col] = $row->{$col} ?? null;
            }

            $attributes = ['id' => $this->registry->getOrMint($this->entity(), $sourceKey)];

            foreach ($this->columns() as $source => $target) {
                if (property_exists($row, $source)) {
                    $attributes[$target] = $row->{$source};
                }
            }

            foreach ($this->foreignKeys() as $target => $fk) {
                $sourceValue = $row->{$fk['source']} ?? null;
                $attributes[$target] = $sourceValue !== null
                    ? $this->registry->getOrMint($fk['entity'], [$fk['source'] => $sourceValue])
                    : null;
            }

            foreach ($this->defaults() as $key => $value) {
                $attributes[$key] = $value;
            }

            $mapped[] = $attributes;
        }

        $mapped = $this->beforeInsert($mapped, $sourceSchema);

        $modelClass = $this->stagingModelClass();
        foreach ($mapped as $attributes) {
            $model = new $modelClass;
            $model->forceFill($attributes)->save();
        }

        return count($mapped);
    }
}
```

- [ ] **Step 4: Run the test**

Run: `php artisan test --compact --filter=TableMapperTest`
Expected: PASS.

- [ ] **Step 5: Add an FK-resolution test**

Append to `tests/Unit/Mapping/TableMapperTest.php`:

```php
it('resolves foreign keys via the registry', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_source');
    DB::statement('CREATE TABLE test_source.album (album_id int, title text, artist_id int)');
    DB::statement("INSERT INTO test_source.album VALUES (10, 'Back in Black', 1)");

    Schema::create('test_staging.albums', function ($t) {
        $t->uuid('id')->primary();
        $t->string('title');
        $t->uuid('artist_id');
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->with('test.albums', ['album_id' => 10])->andReturn('bbbb-0010');
    $registry->shouldReceive('getOrMint')->with('test.artists', ['artist_id' => 1])->andReturn('aaaa-0001');

    $albumModel = get_class(new class extends \Illuminate\Database\Eloquent\Model {
        protected $table = 'test_staging.albums';
        protected $guarded = [];
        public $incrementing = false;
        protected $keyType = 'string';
    });

    $mapper = new class($registry, $albumModel) extends TableMapper
    {
        public function __construct(SourceIdentityRegistry $r, private string $mc)
        {
            parent::__construct($r);
        }
        protected function entity(): string { return 'test.albums'; }
        protected function sourceSchemaTable(): string { return 'test_source.album'; }
        protected function stagingModelClass(): string { return $this->mc; }
        protected function sourceKey(): array { return ['album_id']; }
        protected function columns(): array { return ['title' => 'title']; }
        protected function foreignKeys(): array {
            return ['artist_id' => ['entity' => 'test.artists', 'source' => 'artist_id']];
        }
    };

    $mapper->load('test_source');

    $album = DB::table('test_staging.albums')->first();
    expect($album->artist_id)->toBe('aaaa-0001');
});
```

- [ ] **Step 6: Run + commit**

```bash
php artisan test --compact --filter=TableMapperTest
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/TableMapper.php tests/Unit/Mapping/TableMapperTest.php
git commit -m "feat(import): add TableMapper abstract base — Eloquent write via staging subclasses"
```

### Task 2.2: SelfReferentialMapper

**Files:**

- Create: `app/Services/ProductImport/Mapping/SelfReferentialMapper.php`
- Test: `tests/Unit/Mapping/SelfReferentialMapperTest.php`

**Interfaces:**

- Consumes: `TableMapper`
- Produces: abstract subclass overriding `load()` to insert with self-FK null, then UPDATE in a second pass

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\SelfReferentialMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

uses(RefreshDatabase::class);

it('inserts employees with reports_to null then updates the resolved self-FK', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_source');
    DB::statement('CREATE TABLE test_source.employee (employee_id int, name text, reports_to int)');
    DB::statement("INSERT INTO test_source.employee VALUES (1, 'Boss', NULL), (2, 'Grunt', 1)");

    Schema::create('test_staging.employees', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->uuid('reports_to')->nullable();
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->with('test.employees', ['employee_id' => 1])->andReturn('eeee-0001');
    $registry->shouldReceive('getOrMint')->with('test.employees', ['employee_id' => 2])->andReturn('eeee-0002');

    $modelClass = get_class(new class extends \Illuminate\Database\Eloquent\Model {
        protected $table = 'test_staging.employees';
        protected $guarded = [];
        public $incrementing = false;
        protected $keyType = 'string';
    });

    $mapper = new class($registry, $modelClass) extends SelfReferentialMapper
    {
        public function __construct(SourceIdentityRegistry $r, private string $mc)
        {
            parent::__construct($r);
        }
        protected function entity(): string { return 'test.employees'; }
        protected function sourceSchemaTable(): string { return 'test_source.employee'; }
        protected function stagingModelClass(): string { return $this->mc; }
        protected function sourceKey(): array { return ['employee_id']; }
        protected function columns(): array { return ['name' => 'name']; }
        protected function selfReference(): array {
            return ['column' => 'reports_to', 'entity' => 'test.employees', 'source' => 'employee_id'];
        }
    };

    $mapper->load('test_source');

    $boss = DB::table('test_staging.employees')->where('name', 'Boss')->first();
    $grunt = DB::table('test_staging.employees')->where('name', 'Grunt')->first();
    expect($boss->reports_to)->toBeNull()
        ->and($grunt->reports_to)->toBe('eeee-0001');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=SelfReferentialMapperTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `SelfReferentialMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use Illuminate\Support\Facades\DB;

/**
 * TableMapper for tables with a self-referential FK (e.g. employees.reports_to).
 *
 * Inserts rows with the self-FK column nulled, then resolves and updates it
 * in a second pass so ordering does not matter.
 */
abstract class SelfReferentialMapper extends TableMapper
{
    /**
     * @return array{column: string, entity: string, source: string}
     *   - column: the staging-model self-FK attribute (e.g. "reports_to")
     *   - entity: the registry entity (e.g. "chinook.employees")
     *   - source: the upstream PK column used to look up the referenced row
     */
    abstract protected function selfReference(): array;

    public function load(string $sourceSchema): int
    {
        $ref = $this->selfReference();
        $rows = DB::table($this->sourceSchemaTable())->get()->all();

        /** @var array<int, array<string, mixed>> $mapped */
        $mapped = [];
        /** @var array<string, array{uuid: string, target: string|null}> $bySourceId */
        $bySourceId = [];

        foreach ($rows as $row) {
            $sourceKey = [];
            foreach ($this->sourceKey() as $col) {
                $sourceKey[$col] = $row->{$col} ?? null;
            }

            $uuid = $this->registry->getOrMint($this->entity(), $sourceKey);
            $attributes = ['id' => $uuid];

            foreach ($this->columns() as $source => $target) {
                if (property_exists($row, $source)) {
                    $attributes[$target] = $row->{$source};
                }
            }

            foreach ($this->foreignKeys() as $target => $fk) {
                $sourceValue = $row->{$fk['source']} ?? null;
                $attributes[$target] = $sourceValue !== null
                    ? $this->registry->getOrMint($fk['entity'], [$fk['source'] => $sourceValue])
                    : null;
            }

            foreach ($this->defaults() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Defer the self-reference column
            $attributes[$ref['column']] = null;

            $mapped[] = $attributes;
            $pkCol = $this->sourceKey()[0];
            $bySourceId[(string) $row->{$pkCol}] = [
                'uuid' => $uuid,
                'target' => isset($row->{$ref['column']}) && $row->{$ref['column']} !== null
                    ? (string) $row->{$ref['column']}
                    : null,
            ];
        }

        $mapped = $this->beforeInsert($mapped, $sourceSchema);

        $modelClass = $this->stagingModelClass();
        foreach ($mapped as $attributes) {
            $model = new $modelClass;
            $model->forceFill($attributes)->save();
        }

        // Second pass: resolve self-FK
        foreach ($bySourceId as $entry) {
            if ($entry['target'] !== null && isset($bySourceId[$entry['target']])) {
                DB::table((new $modelClass)->getTable())
                    ->where('id', $entry['uuid'])
                    ->update([$ref['column'] => $bySourceId[$entry['target']]['uuid']]);
            }
        }

        return count($mapped);
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact --filter=SelfReferentialMapperTest
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/SelfReferentialMapper.php tests/Unit/Mapping/SelfReferentialMapperTest.php
git commit -m "feat(import): add SelfReferentialMapper for two-pass self-FK resolution"
```

### Task 2.3: ProductMapper orchestrator

**Files:**

- Create: `app/Services/ProductImport/Mapping/ProductMapper.php`
- Test: `tests/Unit/Mapping/ProductMapperTest.php`

**Interfaces:**

- Consumes: `SourceIdentityRegistry`, list of `TableMapper` instances
- Produces: `load(string $sourceSchema): array{tables: int, rows: int}` — truncate staging domain tables in reverse-FK order, run mappers in a transaction

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\ProductMapper;
use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

uses(RefreshDatabase::class);

it('truncates staging tables then runs each mapper in order inside a transaction', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_source');
    DB::statement('CREATE TABLE test_source.artist (artist_id int, name text)');
    DB::statement("INSERT INTO test_source.artist VALUES (1, 'X')");

    Schema::create('test_staging.artists', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->timestamps();
    });
    DB::table('test_staging.artists')->insert([
        'id' => 'old', 'name' => 'stale', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->andReturn('new-uuid');

    $modelClass = get_class(new class extends \Illuminate\Database\Eloquent\Model {
        protected $table = 'test_staging.artists';
        protected $guarded = [];
        public $incrementing = false;
        protected $keyType = 'string';
    });

    $artistMapper = new class($registry, $modelClass) extends TableMapper
    {
        public function __construct(SourceIdentityRegistry $r, private string $mc)
        {
            parent::__construct($r);
        }
        protected function entity(): string { return 'test.artists'; }
        protected function sourceSchemaTable(): string { return 'test_source.artist'; }
        protected function stagingModelClass(): string { return $this->mc; }
        protected function sourceKey(): array { return ['artist_id']; }
        protected function columns(): array { return ['name' => 'name']; }
    };

    $productMapper = new class($registry, $artistMapper) extends ProductMapper
    {
        public function __construct(SourceIdentityRegistry $r, private TableMapper $m)
        {
            parent::__construct($r);
        }
        protected function mappers(): array { return [$this->m]; }
        protected function truncateOrder(): array { return ['test_staging.artists']; }
    };

    $result = $productMapper->load('test_source');

    expect($result['tables'])->toBe(1)
        ->and($result['rows'])->toBe(1)
        ->and(DB::table('test_staging.artists')->count())->toBe(1)
        ->and(DB::table('test_staging.artists')->first()->name)->toBe('X');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=ProductMapperTest`
Expected: FAIL.

- [ ] **Step 3: Implement `ProductMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates loading all tables for one product: truncate staging domain
 * tables in reverse-FK order, then run each TableMapper in dependency order.
 */
abstract class ProductMapper
{
    public function __construct(
        protected SourceIdentityRegistry $registry,
    ) {}

    /** @return array<int, TableMapper> in dependency (insert) order */
    abstract protected function mappers(): array;

    /** @return array<int, string> fully-qualified staging tables in reverse-FK (truncate) order */
    abstract protected function truncateOrder(): array;

    /**
     * @return array{tables: int, rows: int}
     */
    public function load(string $sourceSchema): array
    {
        $rows = 0;

        DB::transaction(function () use ($sourceSchema, &$rows) {
            foreach ($this->truncateOrder() as $table) {
                DB::table($table)->truncate();
            }

            foreach ($this->mappers() as $mapper) {
                $rows += $mapper->load($sourceSchema);
            }
        });

        return ['tables' => count($this->mappers()), 'rows' => $rows];
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact --filter=ProductMapperTest
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/ProductMapper.php tests/Unit/Mapping/ProductMapperTest.php
git commit -m "feat(import): add ProductMapper orchestrator — truncate + run mappers in transaction"
```

### Task 2.4: Concrete per-product mappers (corrected from audit)

This task implements the 11+11+17 concrete mappers. Because each mapper is small and follows the pattern established in Task 2.1–2.3, they are batched by product with a representative full example + a spec table for the rest. **Read the corrected spec tables carefully — they differ from the original plan (address normalization, bytea blobs, fixed typos).**

**Files:**

- Create: `app/Services/ProductImport/Mapping/Chinook/ChinookProductMapper.php` + 11 mappers
- Create: `app/Services/ProductImport/Mapping/Northwind/NorthwindProductMapper.php` + 11 mappers
- Create: `app/Services/ProductImport/Mapping/Pagila/PagilaProductMapper.php` + 17 mappers (incl. AddressMapper)
- Tests: `tests/Feature/Import/TransformChinookTest.php`, `TransformNorthwindTest.php`, `TransformPagilaTest.php` (see Phase 4)

#### Chinook (11 mappers) — cleanest, 1:1

- [ ] **Step 1: Create `ArtistMapper`** (canonical example)

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Chinook;

use App\Domain\Staging\Chinook\Artist as StagingArtist;
use App\Services\ProductImport\Mapping\TableMapper;

final class ArtistMapper extends TableMapper
{
    protected function entity(): string { return 'chinook.artists'; }
    protected function sourceSchemaTable(): string { return 'chinook_source.artist'; }
    protected function stagingModelClass(): string { return StagingArtist::class; }
    protected function sourceKey(): array { return ['artist_id']; }
    protected function columns(): array { return ['name' => 'name']; }
}
```

- [ ] **Step 2: Create the remaining 10 Chinook mappers per this spec table**

| Mapper                | entity                   | sourceSchemaTable               | sourceKey                    | columns (source=>attr)                                                                                                                                                                                                                   | foreignKeys (attr=>[entity,source])                                                                                               | extends                                                                                                                    |
| --------------------- | ------------------------ | ------------------------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `AlbumMapper`         | `chinook.albums`         | `chinook_source.album`          | `['album_id']`               | `title=>title`                                                                                                                                                                                                                           | `artist_id=>[chinook.artists, artist_id]`                                                                                         | `TableMapper`                                                                                                              |
| `GenreMapper`         | `chinook.genres`         | `chinook_source.genre`          | `['genre_id']`               | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `MediaTypeMapper`     | `chinook.media_types`    | `chinook_source.media_type`     | `['media_type_id']`          | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `EmployeeMapper`      | `chinook.employees`      | `chinook_source.employee`       | `['employee_id']`            | `last_name=>last_name, first_name=>first_name, title=>title, birth_date=>birth_date, hire_date=>hire_date, address=>address, city=>city, state=>state, country=>country, postal_code=>postal_code, phone=>phone, fax=>fax, email=>email` | — (self-ref via SelfReferentialMapper)                                                                                            | `SelfReferentialMapper`, `selfReference(): ['column'=>'reports_to','entity'=>'chinook.employees','source'=>'employee_id']` |
| `CustomerMapper`      | `chinook.customers`      | `chinook_source.customer`       | `['customer_id']`            | `first_name=>first_name, last_name=>last_name, company=>company, address=>address, city=>city, state=>state, country=>country, postal_code=>postal_code, phone=>phone, fax=>fax, email=>email`                                           | `support_rep_id=>[chinook.employees, support_rep_id]`                                                                             | `TableMapper`                                                                                                              |
| `TrackMapper`         | `chinook.tracks`         | `chinook_source.track`          | `['track_id']`               | `name=>name, composer=>composer, milliseconds=>milliseconds, bytes=>bytes, unit_price=>unit_price`                                                                                                                                       | `album_id=>[chinook.albums, album_id], media_type_id=>[chinook.media_types, media_type_id], genre_id=>[chinook.genres, genre_id]` | `TableMapper`                                                                                                              |
| `PlaylistMapper`      | `chinook.playlists`      | `chinook_source.playlist`       | `['playlist_id']`            | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `InvoiceMapper`       | `chinook.invoices`       | `chinook_source.invoice`        | `['invoice_id']`             | `invoice_date=>invoice_date, billing_address=>billing_address, billing_city=>billing_city, billing_state=>billing_state, billing_country=>billing_country, billing_postal_code=>billing_postal_code, total=>total`                       | `customer_id=>[chinook.customers, customer_id]`                                                                                   | `TableMapper`                                                                                                              |
| `PlaylistTrackMapper` | `chinook.playlist_track` | `chinook_source.playlist_track` | `['playlist_id','track_id']` | —                                                                                                                                                                                                                                        | `playlist_id=>[chinook.playlists, playlist_id], track_id=>[chinook.tracks, track_id]`                                             | `TableMapper`                                                                                                              |
| `InvoiceLineMapper`   | `chinook.invoice_lines`  | `chinook_source.invoice_line`   | `['invoice_line_id']`        | `unit_price=>unit_price, quantity=>quantity`                                                                                                                                                                                             | `invoice_id=>[chinook.invoices, invoice_id], track_id=>[chinook.tracks, track_id]`                                                | `TableMapper`                                                                                                              |

Each mapper's `stagingModelClass()` returns the matching `App\Domain\Staging\Chinook\<X>::class`.

- [ ] **Step 3: Create `ChinookProductMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Chinook;

use App\Services\ProductImport\Mapping\ProductMapper;

final class ChinookProductMapper extends ProductMapper
{
    protected function mappers(): array
    {
        return [
            new ArtistMapper($this->registry),
            new GenreMapper($this->registry),
            new MediaTypeMapper($this->registry),
            new EmployeeMapper($this->registry),
            new AlbumMapper($this->registry),
            new CustomerMapper($this->registry),
            new TrackMapper($this->registry),
            new PlaylistMapper($this->registry),
            new InvoiceMapper($this->registry),
            new PlaylistTrackMapper($this->registry),
            new InvoiceLineMapper($this->registry),
        ];
    }

    protected function truncateOrder(): array
    {
        return [
            'chinook_staging.invoice_lines',
            'chinook_staging.playlist_track',
            'chinook_staging.invoices',
            'chinook_staging.playlists',
            'chinook_staging.tracks',
            'chinook_staging.customers',
            'chinook_staging.albums',
            'chinook_staging.employees',
            'chinook_staging.media_types',
            'chinook_staging.genres',
            'chinook_staging.artists',
        ];
    }
}
```

- [ ] **Step 4: Run transform tests (Phase 4 Task 4.2 fixture must exist)**

```bash
php artisan test --compact --filter=TransformChinook
```

Expected: PASS.

#### Northwind (11 mappers) — string PKs, bytea blobs, discontinued cast

- [ ] **Step 5: Create the 11 Northwind mappers per this spec table**

| Mapper                    | entity                           | sourceSchemaTable                       | sourceKey                        | columns                                                                                                                                                                                                                                                                                                                                      | foreignKeys / notes                                                                                                                            | extends                                                                                                                      |
| ------------------------- | -------------------------------- | --------------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `CategoryMapper`          | `northwind.categories`           | `northwind_source.categories`           | `['category_id']`                | `category_name=>category_name, description=>description, picture=>picture`                                                                                                                                                                                                                                                                   | — (picture now bytea, pass-through)                                                                                                            | `TableMapper`                                                                                                                |
| `CustomerMapper`          | `northwind.customers`            | `northwind_source.customers`            | `['customer_id']` (string PK)    | `company_name=>company_name, contact_name=>contact_name, contact_title=>contact_title, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, phone=>phone, fax=>fax`                                                                                                                                     | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `EmployeeMapper`          | `northwind.employees`            | `northwind_source.employees`            | `['employee_id']`                | `last_name=>last_name, first_name=>first_name, title=>title, title_of_courtesy=>title_of_courtesy, birth_date=>birth_date, hire_date=>hire_date, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, home_phone=>home_phone, extension=>extension, photo=>photo, notes=>notes, photo_path=>photo_path` | — (self-ref)                                                                                                                                   | `SelfReferentialMapper`, `selfReference(): ['column'=>'reports_to','entity'=>'northwind.employees','source'=>'employee_id']` |
| `RegionMapper`            | `northwind.regions`              | `northwind_source.region`               | `['region_id']`                  | `region_description=>region_description`                                                                                                                                                                                                                                                                                                     | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `TerritoryMapper`         | `northwind.territories`          | `northwind_source.territories`          | `['territory_id']` (string PK)   | `territory_description=>territory_description`                                                                                                                                                                                                                                                                                               | `region_id=>[northwind.regions, region_id]`                                                                                                    | `TableMapper`                                                                                                                |
| `EmployeeTerritoryMapper` | `northwind.employee_territories` | `northwind_source.employee_territories` | `['employee_id','territory_id']` | —                                                                                                                                                                                                                                                                                                                                            | `employee_id=>[northwind.employees, employee_id], territory_id=>[northwind.territories, territory_id]`                                         | `TableMapper`                                                                                                                |
| `ShipperMapper`           | `northwind.shippers`             | `northwind_source.shippers`             | `['shipper_id']`                 | `company_name=>company_name, phone=>phone`                                                                                                                                                                                                                                                                                                   | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `SupplierMapper`          | `northwind.suppliers`            | `northwind_source.suppliers`            | `['supplier_id']`                | `company_name=>company_name, contact_name=>contact_name, contact_title=>contact_title, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, phone=>phone, fax=>fax, homepage=>homepage`                                                                                                                 | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `ProductMapper`           | `northwind.products`             | `northwind_source.products`             | `['product_id']`                 | `product_name=>product_name, quantity_per_unit=>quantity_per_unit, unit_price=>unit_price, units_in_stock=>units_in_stock, units_on_order=>units_on_order, reorder_level=>reorder_level`                                                                                                                                                     | `supplier_id=>[northwind.suppliers, supplier_id], category_id=>[northwind.categories, category_id]`                                            | `TableMapper` + override `beforeInsert()` to cast `discontinued` int→bool                                                    |
| `OrderMapper`             | `northwind.orders`               | `northwind_source.orders`               | `['order_id']`                   | `order_date=>order_date, required_date=>required_date, shipped_date=>shipped_date, freight=>freight, ship_name=>ship_name, ship_address=>ship_address, ship_city=>ship_city, ship_region=>ship_region, ship_postal_code=>ship_postal_code, ship_country=>ship_country`                                                                       | `customer_id=>[northwind.customers, customer_id], employee_id=>[northwind.employees, employee_id], ship_via=>[northwind.shippers, shipper_id]` | `TableMapper`                                                                                                                |
| `OrderDetailMapper`       | `northwind.order_details`        | `northwind_source.order_details`        | `['order_id','product_id']`      | `unit_price=>unit_price, quantity=>quantity, discount=>discount`                                                                                                                                                                                                                                                                             | `order_id=>[northwind.orders, order_id], product_id=>[northwind.products, product_id]`                                                         | `TableMapper`                                                                                                                |

`ProductMapper` with the `discontinued` cast:

```php
protected function beforeInsert(array $rows, string $sourceSchema): array
{
    foreach ($rows as &$row) {
        $row['discontinued'] = (bool) ($row['discontinued'] ?? 0);
    }

    return $rows;
}
```

Omit `customer_demographics`, `customer_customer_demo`, `us_states` (no app counterpart).

- [ ] **Step 6: Create `NorthwindProductMapper`** (mirror Chinook; `truncateOrder()` reverse-FK: order_details, orders, employee_territories, territories, products, shippers, suppliers, regions, employees, customers, categories — all `northwind_staging.` prefixed)

#### Pagila (17 mappers — incl. AddressMapper)

- [ ] **Step 7: Create the 17 Pagila mappers per this spec table**

| Mapper               | entity                   | sourceSchemaTable                                       | sourceKey                   | columns                                                                                                                                                                              | foreignKeys / notes                                                                                                                                                                       |
| -------------------- | ------------------------ | ------------------------------------------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `CountryMapper`      | `pagila.countries`       | `pagila_source.country`                                 | `['country_id']`            | `country=>country`                                                                                                                                                                   | —                                                                                                                                                                                         |
| `CityMapper`         | `pagila.cities`          | `pagila_source.city`                                    | `['city_id']`               | `city=>city`                                                                                                                                                                         | `country_id=>[pagila.countries, country_id]`                                                                                                                                              |
| **`AddressMapper`**  | `pagila.addresses`       | `pagila_source.address`                                 | `['address_id']`            | `address=>address, address2=>address2, district=>district, postal_code=>postal_code, phone=>phone`                                                                                   | `city_id=>[pagila.cities, city_id]`                                                                                                                                                       |
| `LanguageMapper`     | `pagila.languages`       | `pagila_source.language`                                | `['language_id']`           | `name=>name`                                                                                                                                                                         | —                                                                                                                                                                                         |
| `ActorMapper`        | `pagila.actors`          | `pagila_source.actor`                                   | `['actor_id']`              | `first_name=>first_name, last_name=>last_name`                                                                                                                                       | —                                                                                                                                                                                         |
| `CategoryMapper`     | `pagila.categories`      | `pagila_source.category`                                | `['category_id']`           | `name=>name`                                                                                                                                                                         | —                                                                                                                                                                                         |
| `StaffMapper`        | `pagila.staff`           | `pagila_source.staff`                                   | `['staff_id']`              | `first_name=>first_name, last_name=>last_name, email=>email, active=>active, username=>username, password=>password, picture=>picture`                                               | `store_id=>[pagila.stores, store_id], address_id=>[pagila.addresses, address_id]`                                                                                                         |
| `StoreMapper`        | `pagila.stores`          | `pagila_source.store`                                   | `['store_id']`              | —                                                                                                                                                                                    | `manager_staff_id=>[pagila.staff, staff_id], address_id=>[pagila.addresses, address_id]`. **Must run in same transaction as Staff (circular FK DEFERRABLE).**                             |
| `CustomerMapper`     | `pagila.customers`       | `pagila_source.customer`                                | `['customer_id']`           | `first_name=>first_name, last_name=>last_name, email=>email, active=>activebool`                                                                                                     | `store_id=>[pagila.stores, store_id], address_id=>[pagila.addresses, address_id]`                                                                                                         |
| `FilmMapper`         | `pagila.films`           | `pagila_source.film`                                    | `['film_id']`               | `title=>title, description=>description, release_year=>release_year, rental_duration=>rental_duration, rental_rate=>rental_rate, length=>length, replacement_cost=>replacement_cost` | `language_id=>[pagila.languages, language_id], original_language_id=>[pagila.languages, original_language_id]`. `beforeInsert`: cast `rating` enum→string, `special_features` array→text. |
| `FilmActorMapper`    | `pagila.film_actors`     | `pagila_source.film_actor`                              | `['actor_id','film_id']`    | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], actor_id=>[pagila.actors, actor_id]`                                                                                                                   |
| `FilmCategoryMapper` | `pagila.film_categories` | `pagila_source.film_category`                           | `['film_id','category_id']` | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], category_id=>[pagila.categories, category_id]`                                                                                                         |
| `FilmTextMapper`     | `pagila.film_texts`      | `pagila_source.film` (re-reads)                         | `['film_id']`               | `title=>title, description=>description`                                                                                                                                             | `film_id=>[pagila.films, film_id]`. (Derived — no upstream film_text table.)                                                                                                              |
| `InventoryMapper`    | `pagila.inventories`     | `pagila_source.inventory`                               | `['inventory_id']`          | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], store_id=>[pagila.stores, store_id]`                                                                                                                   |
| `RentalMapper`       | `pagila.rentals`         | `pagila_source.rental`                                  | `['rental_id']`             | `rental_date=>rental_date, return_date=>return_date`                                                                                                                                 | `inventory_id=>[pagila.inventories, inventory_id], customer_id=>[pagila.customers, customer_id], staff_id=>[pagila.staff, staff_id]`                                                      |
| `PaymentMapper`      | `pagila.payments`        | `pagila_source.payment` (or partition union — see note) | `['payment_id']`            | `amount=>amount, payment_date=>payment_date`                                                                                                                                         | `customer_id=>[pagila.customers, customer_id], staff_id=>[pagila.staff, staff_id], rental_id=>[pagila.rentals, rental_id]`.                                                               |

**Payment partition note:** if the `PostgresSourceReader` leaves `payment_p2022_*` tables as separate staging tables, override `sourceSchemaTable()` in `PaymentMapper` to a `UNION ALL` subquery (via a `DB::raw` view or a raw query). Verify at Phase 5 against real data; for fixtures (single `payment` table), the default works.

- [ ] **Step 8: Create `PagilaProductMapper`** with `truncateOrder()` reverse-FK and `mappers()` insert order. **Staff and Stores must be adjacent and inside the transaction** (circular FK). Insert order: countries → cities → addresses → languages → actors → categories → staff → stores → customers → films → film_actors → film_categories → film_texts → inventories → rentals → payments.

```php
protected function truncateOrder(): array
{
    return [
        'pagila_staging.payments', 'pagila_staging.rentals', 'pagila_staging.inventories',
        'pagila_staging.film_texts', 'pagila_staging.film_categories', 'pagila_staging.film_actors',
        'pagila_staging.films', 'pagila_staging.customers', 'pagila_staging.stores',
        'pagila_staging.staff', 'pagila_staging.categories', 'pagila_staging.actors',
        'pagila_staging.languages', 'pagila_staging.addresses', 'pagila_staging.cities',
        'pagila_staging.countries',
    ];
}
```

- [ ] **Step 9: Run all transform tests + pint + commit**

```bash
php artisan test --compact --filter=Transform
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/
git commit -m "feat(import): implement corrected per-product mappers (Chinook 11, Northwind 11, Pagila 17 w/ addresses)"
```

---

## Phase 3 — Importer rewrite

### Task 3.1: Three-schema importer flow

**Files:**

- Modify: `app/Services/ProductImport/ChinookImporter.php`
- Modify: `app/Services/ProductImport/NorthwindImporter.php`
- Modify: `app/Services/ProductImport/PagilaImporter.php`
- Create: `app/Services/ProductImport/EmbeddingDrain.php`
- Test: `tests/Feature/Import/SchemaPreservationTest.php` (rewrite expectation: view recreated post-swap)

**Interfaces:**

- Consumes: `StagingSchemaBuilder`, `PortfolioViewRecreator`, `ChinookProductMapper`/`NorthwindProductMapper`/`PagilaProductMapper`, `EmbeddingDrain`, `PostgresSourceReader`
- Produces: importers implementing the full six-phase shadow-swap flow

- [ ] **Step 1: Implement `EmbeddingDrain`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Jobs\EmbeddingJob;
use Illuminate\Support\Facades\DB;

/**
 * Post-publish rebuild phase: dispatch EmbeddingJob for every pending projection
 * row, then wait for drain. Required for Baseline Invariant 8 (#28 Decision 10).
 */
final class EmbeddingDrain
{
    public function __construct(
        private int $softTimeoutSeconds = 300,
        private int $hardTimeoutSeconds = 1800,
    ) {}

    /**
     * @return array{queued: int, drained: int, timed_out: bool}
     */
    public function drain(string $product): array
    {
        $pending = DB::table("{$product}.search_projections")
            ->where('embedding_state', 'pending')
            ->pluck('id');

        foreach ($pending as $id) {
            EmbeddingJob::dispatch($product, $id);
        }

        $start = time();
        $drained = 0;
        $timedOut = false;

        $remaining = 0;
        while (true) {
            $remaining = DB::table("{$product}.search_projections")
                ->where('embedding_state', 'pending')
                ->count();

            if ($remaining === 0) {
                break;
            }

            $elapsed = time() - $start;
            if ($elapsed >= $this->hardTimeoutSeconds) {
                $timedOut = true;
                break;
            }

            sleep(5);
        }

        return [
            'queued' => $pending->count(),
            'drained' => $pending->count() - $remaining,
            'timed_out' => $timedOut,
        ];
    }
}
```

Note: the `while (true)` poll is acceptable for the CLI foreground path. For the queued-job path (`--wait=off`), the drain is asynchronous and the operator monitors via `product:status`. For sample-data scale (~5K rows), drain completes in seconds to minutes depending on the embedding API.

- [ ] **Step 2: Rewrite `ChinookImporter::import()`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ChinookImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
        private StagingSchemaBuilder $stagingBuilder,
        private ChinookProductMapper $mapper,
        private PortfolioViewRecreator $viewRecreator,
        private EmbeddingDrain $drain,
    ) {}

    /**
     * Execute Chinook import via the shadow-swap pipeline.
     *
     * @return array{success: bool, error?: string}
     */
    public function import(bool $dryRun = false, ?ResetRun $run = null): array
    {
        if ($dryRun) {
            return ['success' => true];
        }

        $sourceFile = $this->getSourceFilePath();

        if ($sourceFile === null || ! File::exists($sourceFile)) {
            return ['success' => true]; // no cached source — no-op
        }

        try {
            // Phase 1: source load (upstream shapes, scratch)
            DB::statement('DROP SCHEMA IF EXISTS chinook_source CASCADE');
            DB::statement('CREATE SCHEMA chinook_source');
            $this->pgReader->executeSqlDump($sourceFile, 'chinook_source');

            // Phase 2: stage build (app shapes + triggers, empty)
            $this->stagingBuilder->build('chinook');

            // Phase 3: transform (Eloquent writes to staging via subclass models)
            app()->instance('is_staging', true);
            try {
                $this->mapper->load('chinook_source');
            } finally {
                app()->forgetInstance('is_staging');
            }

            // Phase 4: validate (light — full invariants are #81's scope)

            // Phase 5: publish (atomic shadow-swap)
            DB::transaction(function () {
                DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE');
                DB::statement('ALTER SCHEMA chinook_staging RENAME TO chinook');
            });
            $this->viewRecreator->recreate();
            DB::statement('DROP SCHEMA IF EXISTS chinook_source CASCADE');

            // Phase 6: rebuild (ANALYZE + embedding drain)
            DB::statement('ANALYZE');
            $this->drain->drain('chinook');

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getSourceFilePath(): ?string
    {
        $manifestPath = database_path('sources/chinook.php');

        if (! File::exists($manifestPath)) {
            return null;
        }

        /** @var array{product: string, commit_sha: string, filename: string} $manifest */
        $manifest = require $manifestPath;

        return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$manifest['filename']}");
    }
}
```

- [ ] **Step 3: Apply the same shape to `NorthwindImporter`** (use `NorthwindProductMapper`, `northwind_source`/`northwind_staging`)

- [ ] **Step 4: Apply to `PagilaImporter`** (use `PagilaProductMapper`, `pagila_source`/`pagila_staging`; two source files — schema then data — loaded into `pagila_source`)

```php
// in PagilaImporter::import(), Phase 1 becomes:
$manifest = $this->getManifest();
$schemaPath = $this->getSourceFilePath($manifest['schema_filename']);
$dataPath = $this->getSourceFilePath($manifest['data_filename']);
$this->pgReader->executeSqlDump($schemaPath, 'pagila_source');
$this->pgReader->executeSqlDump($dataPath, 'pagila_source');
```

- [ ] **Step 5: Update DI bindings** — the importers now have more constructor dependencies. If the app uses auto-resolution (Laravel container), no explicit binding needed. Confirm by checking `app/Providers/AppServiceProvider.php` for any manual bindings.

- [ ] **Step 6: Write the schema-preservation test (adjusted)**

```php
// tests/Feature/Import/SchemaPreservationTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\ChinookImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('recreates the product_portfolio_snapshots view after a chinook import publish', function () {
    // This test requires a cached source file; in CI without one, the importer no-ops.
    // Use the minimal fixture instead (Phase 4 Task 4.2) to drive a real transform.
    $this->markTestSkipped('Driven by BehavioralComplianceTest with fixture; see Task 4.1.');

    // Kept as documentation of the contract: post-swap, the view exists.
    $exists = DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') IS NOT NULL AS e")->e;
    expect($exists)->toBeTrue();
});
```

(The real coverage is in `BehavioralComplianceTest`, Task 4.1.)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/EmbeddingDrain.php \
        app/Services/ProductImport/ChinookImporter.php \
        app/Services/ProductImport/NorthwindImporter.php \
        app/Services/ProductImport/PagilaImporter.php \
        tests/Feature/Import/SchemaPreservationTest.php
git commit -m "feat(import): restore shadow-swap flow + embedding drain in all three importers"
```

### Task 3.2: Wire ResetEvidence into the pipeline

**Files:**

- Modify: `app/Services/ProductImport/ProductImportPipeline.php`
- Test: `tests/Feature/Import/ProductImportPipelineTest.php` (extend)

- [ ] **Step 1: Update the pipeline** to build a `ResetEvidence` and serialize it into the run's `evidence` column on both success and failure paths. Read `app/Services/ProductReset/ResetEvidence.php` for its API (`setSection`, `toArray`). At minimum, populate `execution_summary` with product, kind, final status, duration; leave the full invariant/evidence sections for #81.

- [ ] **Step 2: Extend the pipeline test** to assert the `evidence` JSONB contains `schema_version: 1` and an `execution_summary` section after a run.

- [ ] **Step 3: Run + commit**

```bash
php artisan test --compact --filter=ProductImportPipeline
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/ProductImportPipeline.php tests/Feature/Import/ProductImportPipelineTest.php
git commit -m "feat(import): wire ResetEvidence value object into run record"
```

### Task 3.3: Confirm `BelongsToProductDomain` arch-rule refinement

- [ ] **Step 1: Locate the arch test** for the `BelongsToProductDomain` mandate (search `tests/` for the rule from #29). Update it to scope the mandate to `App\Models\<Product>\` and explicitly exempt `App\Domain\Staging\`. If no such arch test exists yet, add one:

```php
it('every live product model uses BelongsToProductDomain, and no staging model does', function () {
    $liveModels = collect(glob(app_path('Models/{Chinook,Northwind,Pagila}/*.php')))
        ->map(fn ($p) => 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], after($p, 'app/Models/')));

    foreach ($liveModels as $class) {
        if (! class_exists($class)) {
            continue;
        }
        expect(in_array('App\Traits\BelongsToProductDomain', class_uses($class) ?: [], true))->toBeTrue($class);
    }

    $stagingModels = collect(glob(app_path('Domain/Staging/**/*.php')))
        ->map(fn ($p) => 'App\\Domain\\Staging\\'.str_replace(['/', '.php'], ['\\', ''], after($p, 'app/Domain/Staging/')));

    foreach ($stagingModels as $class) {
        if (! class_exists($class)) {
            continue;
        }
        expect(in_array('App\Traits\BelongsToProductDomain', class_uses($class) ?: [], true))->toBeFalse($class);
    }
});
```

- [ ] **Step 2: Run + commit**

```bash
php artisan test --compact --filter=BelongsToProductDomain
git add tests/
git commit -m "test(import): refine #29 arch rule — staging models exempt from BelongsToProductDomain"
```

---

## Phase 4 — Tests

### Task 4.1: Behavioral-compliance test (#81, the prerequisite blocker)

**Files:**

- Create: `tests/Feature/Import/BehavioralComplianceTest.php`

This is the standing regression. It will fail red until the full pipeline works, then stay green.

- [ ] **Step 1: Write the test**

For each product: load minimal fixture → run `StagingSchemaBuilder` + `ProductMapper::load` → assert (a) domain tables populated with UUIDv7 PKs, (b) FKs resolved, (c) portfolio view exists post-swap, (d) `search_projections` populated with correct `embedding_state` per tier, (e) triggers wrote correct `weight_*` per #31. See the fixture files (Task 4.2).

Example (Chinook):

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use App\Services\ProductImport\StagingSchemaBuilder;
use App\Services\ProductImport\PortfolioViewRecreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('compliance: chinook import populates domain tables with UUID PKs and resolves FKs', function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_source CASCADE');
    DB::statement('CREATE SCHEMA chinook_source');
    app(PostgresSourceReader::class)->executeSqlDump(
        base_path('tests/Fixtures/Sources/chinook/minimal.sql'), 'chinook_source',
    );

    app(StagingSchemaBuilder::class)->build('chinook');
    app()->instance('is_staging', true);
    try {
        app(ChinookProductMapper::class)->load('chinook_source');
    } finally {
        app()->forgetInstance('is_staging');
    }

    // Swap
    DB::transaction(function () {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE');
        DB::statement('ALTER SCHEMA chinook_staging RENAME TO chinook');
    });
    app(PortfolioViewRecreator::class)->recreate();

    expect(DB::table('chinook.artists')->count())->toBeGreaterThan(0)
        ->and(DB::table('chinook.tracks')->count())->toBeGreaterThan(0);

    // UUID PKs
    $artist = DB::table('chinook.artists')->first();
    expect($artist->id)->toMatch('/^[0-9a-f-]{36}$/');

    // FK resolved
    $track = DB::table('chinook.tracks')->first();
    $album = DB::table('chinook.albums')->first();
    expect($track->album_id)->toBe($album->id);

    // View exists
    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') IS NOT NULL AS e")->e)->toBeTrue();

    // Search projections — tier-1 pending, tier-2 lexical_only
    $pending = DB::table('chinook.search_projections')->where('embedding_state', 'pending')->count();
    $lexical = DB::table('chinook.search_projections')->where('embedding_state', 'lexical_only')->count();
    expect($pending)->toBeGreaterThan(0) // artists/albums/tracks/playlists
        ->and($lexical)->toBeGreaterThan(0); // customers/employees/invoices/genres

    // #31 weights: tracks have B-weight from genre
    $trackProj = DB::table('chinook.search_projections')->where('entity_type', 'tracks')->first();
    expect($trackProj->weight_b_text)->not->toBeNull();
});
```

- [ ] **Step 2: Run to verify red/green**

Run: `php artisan test --compact --filter=BehavioralCompliance`
Expected: RED initially, GREEN after Phases 0–3 complete.

### Task 4.2: Fixtures

- [ ] **Step 1: `tests/Fixtures/Sources/chinook/minimal.sql`** — minimal Chinook dump (upstream table/column names, `public.`-qualified — `PostgresSourceReader` rewrites). 2 artists, 1 album, 1 genre, 1 media_type, 2 employees (self-ref), 1 customer, 1 track, 1 playlist, 1 playlist_track, 1 invoice, 1 invoice_line. (Copy from the original plan's Task C1 fixture; it was correct.)

- [ ] **Step 2: `tests/Fixtures/Sources/northwind/minimal.sql`** — minimal Northwind dump. Include a small `bytea` value for `categories.picture` and `employees.photo` (e.g. `decode('89504E470D0A1A0A', 'hex')`). String PK customer "ALFKI". (Adapt from original plan Task D1, adding bytea blobs.)

- [ ] **Step 3: `tests/Fixtures/Sources/pagila/schema-minimal.sql` + `data-minimal.sql`** — minimal Pagila dumps. Include the **normalized `address` table** (1 row), referenced by staff/customer/store. Circular staff↔store. (Adapt from original plan Task E1; add address row.)

- [ ] **Step 4: Commit**

```bash
git add tests/Fixtures/Sources/
git commit -m "test(import): add corrected minimal fixtures (Pagila w/ normalized address, Northwind w/ bytea blobs)"
```

### Task 4.3: Per-product transform tests + trigger-coverage tests

- [ ] **Step 1: `TransformChinookTest.php`** — assert counts, FK resolution, idempotency, self-ref (reports_to).
- [ ] **Step 2: `TransformNorthwindTest.php`** — assert string-PK customer round-trips, self-ref, `discontinued` is boolean, bytea blobs land.
- [ ] **Step 3: `TransformPagilaTest.php`** — assert address normalization (FK to addresses), circular staff↔store FK resolves, payments collapse.
- [ ] **Step 4: `SearchProjectionMappingTest.php`** — for each product, assert every tier-1/tier-2 entity has a projection row with #31-correct `weight_*` and `embedding_state`; tier-3 entities have NO projection row.

### Task 4.4: Commit tests

```bash
php artisan test --compact --filter=Transform
php artisan test --compact --filter=SearchProjectionMapping
vendor/bin/pint --dirty --format agent
git add tests/Feature/Import/
git commit -m "test(import): per-product transform tests + #31 trigger-coverage tests"
```

---

## Phase 5 — Gates

### Task 5.1: Full suite + real import

- [ ] **Step 1: Full test suite**

```bash
php artisan test --compact
```

Expected: all green. Investigate and fix any failures.

- [ ] **Step 2: Pint the whole project**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 3: Real import (or fixture fallback)**

```bash
php artisan source:fetch chinook && php artisan product:import chinook
```

If `source:fetch` fails (network/auth), the transform tests prove the pipeline works against fixtures; note the fetch failure as a follow-up bead.

- [ ] **Step 4: Verify `/admin` + portfolio + semantic search**

```bash
php artisan tinker --execute 'echo Illuminate\Support\Facades\DB::table("chinook.search_projections")->where("embedding_state","pending")->count();'
```

Expected: `0` (after embedding drain). Open `/admin` and `/admin/portfolio` in a browser — expect no `QueryException`, counts reflect imported data.

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "test: full suite green after import-pipeline completion"
```

- [ ] **Step 6: Update the beads tracker**

```bash
bd ready
bd close <id>  # close completed beads for this work
```

---

## Self-Review

**1. Spec coverage (against the approved plan + audit findings):**

- ✅ Shadow-swap restored, view recreated post-swap (Tasks 0.4, 3.1)
- ✅ Eloquent sole write path; staging subclasses exempt from trait (Tasks 1.1, 2.1)
- ✅ `is_staging` wired (Task 1.3 verify + Task 3.1 apply)
- ✅ Search-projection triggers rewritten with `TG_TABLE_SCHEMA` (Task 0.3) — fixes swap-isolation bug
- ✅ #31 field→weight mapping implemented, B-weight live (Task 0.3)
- ✅ Pagila addresses normalized (Task 0.1, Pagila AddressMapper in 2.4)
- ✅ Blob columns → bytea (Task 0.2)
- ✅ Mapping gaps fixed: reports_to typo, address_source_id, film_texts derivation, partition collapse (Task 2.4)
- ✅ Model fixes: phantoms deleted, Chinook casts added (Task 0.5)
- ✅ Embedding drain post-publish — the step the raw-insert deviation omitted (Task 3.1)
- ✅ #81 behavioral-compliance test (Task 4.1)
- ✅ Corrected fixtures incl. Pagila address + Northwind bytea (Task 4.2)

**2. Placeholder scan:** No TBD/TODO. The `PaymentMapper` partition-collapse note (Task 2.4) is a genuine runtime unknown flagged for Phase 5 verification, with a default path (single `payment` table) fully specified. The `StagingSchemaBuilder` cloning uses `DB::unprepared` with `pg_dump` — a concrete choice, not a placeholder.

**3. Type consistency:**

- `TableMapper::load(string $sourceSchema): int` — consistent across base, `SelfReferentialMapper`, all concrete mappers.
- `ProductMapper::load(string $sourceSchema): array{tables: int, rows: int}` — consistent.
- `SourceIdentityRegistry::getOrMint(string $entity, array $sourceKey): string` — used identically in `TableMapper` and `SelfReferentialMapper`.
- `entity()` values match the `source_identities.entity` CHECK regex in all mappers.
- Staging model class names consistent between `Domain/Staging/` and `stagingModelClass()` returns.
- `truncateOrder()` targets `<product>_staging.*` consistently (the staging models' tables).

---

## Open questions for execution (not blocking)

1. **`StagingSchemaBuilder` `pg_dump` availability** — Herd provides `pg_dump`; CI Postgres should too. If the execution environment lacks it, fall back to catalog introspection (`pg_class`/`pg_constraint`/`pg_index`/`pg_trigger` → re-issue DDL). Pin at Task 1.2.
2. **Trigger `weight_b` FK-lookup** — the `EXECUTE format(...)` pattern inside the trigger function must correctly qualify the lookup table with `TG_TABLE_SCHEMA`. The Chinook genre lookup (Task 0.3) uses this; confirm it works for the many-to-many Pagila film→category case (film_category join).
3. **Real upstream dump schema qualification** — `PostgresSourceReader`'s blind `public.` rewrite assumes upstream uses `public.`. Verify against real Chinook/Northwind/Pagila dumps at Task 5.1; adjust the rewrite rule if they don't.

---

## Out of scope

- Full Baseline Invariants 1–8 implementation (#28 Decision 10) — structure present, full check suite is #81 ongoing.
- `source:purge` command — not needed for completion.
- Filament Admin UI for runs — #26 scope.
- Reset confirmation flow for first-import — #29 skips it for `kind=import`.
- `session_replication_role` / `DISABLE TRIGGER` — rejected per deviation analysis §1.C.
