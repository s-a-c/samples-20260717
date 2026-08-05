# Import Cascade Fix & Upstream→UUID Transform — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop the Import pipeline from destroying the `product_portfolio_snapshots` view (and each product's search-projection objects), and build the real upstream→UUID domain transform layer so imports load upstream rows into the app's UUID domain tables instead of replacing them with incompatible integer-PK upstream tables.

**Architecture:** Remove `DROP SCHEMA … CASCADE` from the three importers and three schema migrations. Introduce a declarative transform layer (`TableMapper` / `ProductMapper`) that loads the upstream dump into a staging schema, then for each app-domain table: truncates it, maps rows through `SourceIdentityRegistry` (stable source-key → UUIDv7), resolves foreign keys via the same registry, and bulk-inserts via raw `DB::table()` (Eloquent writes are blocked by `BelongsToProductDomain` during a running `ResetRun`). Each product (chinook/northwind/pagila) gets a concrete mapper family.

**Tech Stack:** Laravel 13, PHP 8.5, PostgreSQL (multi-schema: `public`, `chinook`, `northwind`, `pagila`, `*_staging`), Pest 5, Spatie Permission.

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

## Root-Cause Diagnosis (context for implementers)

`product_portfolio_snapshots` is a PostgreSQL **VIEW** in `public` that references `chinook.artists`, `chinook.tracks`, `northwind.products`, `northwind.orders`, `pagila.films`, `pagila.actors`. The Import pipeline destroys it on every run:

```php
// app/Services/ProductImport/ChinookImporter.php:37 (Northwind/Pagila identical)
DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');          // <- destroys the VIEW
DB::statement('ALTER SCHEMA chinook_staging RENAME TO chinook;');
```

Postgres `DROP SCHEMA … CASCADE` recursively drops **all dependent objects across every schema**. Because `public.product_portfolio_snapshots` depends on `chinook.*`, the CASCADE silently drops the view. The same blast radius destroys each product's `*.search_projections` table, GIN/HNSW indexes, trigger functions, and triggers (created by the `210001`/`211001`/`212001` migrations). The `migrations` table still records `213000` (view) as "ran", so `php artisan migrate` will **never recreate it**.

The importer is also **structurally incomplete**: it never transforms upstream rows (integer PKs, upstream columns) into the app's UUID domain tables. It "succeeds" today only because no source dump is cached (`storage/app/private/sources/*` dirs are empty), so it swaps in an empty staging schema.

## Critical Design Constraints (read before implementing)

1. **Eloquent writes are blocked during import.** `BelongsToProductDomain` boots `creating`/`updating`/`deleting` hooks that call `ResetWindow::assertWritable()`, which throws `ProductResetWindowOpen` while a `ResetRun` is `running`. Since `ProductImportPipeline::run()` marks the run `running` _before_ calling the importer, **the transform MUST use raw `DB::table()->insert()`**, not Eloquent models. This is why `SourceIdentityRegistry` already uses `SourceIdentity::create()` outside the domain models — but during a run even that goes through Eloquent; the registry must be invoked via its own raw path (see Task B1).
2. **`SourceIdentityRegistry` is the intended FK-translation layer** (already exists at `app/Services/ProductImport/SourceIdentityRegistry.php`, idempotent). `getOrMint("chinook.artists", ["artist_id" => 5])` returns a stable UUIDv7 — same source row always maps to the same UUID across re-imports. The `source_identities` table has a CHECK constraint: `entity ~ '^(chinook|northwind|pagila)\.[a-z_][a-z0-9_]*$'`. The `domain_id` is the UUID written into the app-domain table's PK and FK columns.
3. **Pagila has a circular FK** (`staff.store_id` ↔ `stores.manager_staff_id`) made `DEFERRABLE INITIALLY DEFERRED` in `database/migrations/pagila/2026_07_24_212000_…` (lines 70-71). Inserts for staff+stores MUST happen inside one transaction.
4. **Self-referential FKs** (Chinook `employees.reports_to`, Northwind `employees.reports_to`) need a two-pass insert: insert with `reports_to = null`, collect the source→UUID map, then `UPDATE` to set the resolved value.
5. **No `product`/`product_domain` column exists** on any domain table. Domain membership is code-level (`getProductDomain()` returns a hardcoded enum). Do NOT write a product column.
6. **Some upstream tables have no app-domain counterpart** and are silently omitted (see Mapping Reference per product).
7. **`SourceIdentityRegistry::getOrMint` currently uses `SourceIdentity::create()` (Eloquent)** — that insert hits the `source_identities` table which is NOT itself domain-gated (no `BelongsToProductDomain` trait), so it is safe during a run. Confirmed: `SourceIdentity` model (`app/Models/SourceIdentity.php`) uses only `HasUuids`, no domain trait.

---

## File Structure

### Files to Create

| File                                                                           | Responsibility                                                                                                                                                                                                            |
| ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Services/ProductImport/Mapping/TableMapper.php`                           | Abstract per-table mapper: declares entity, staging table, domain table, source key columns, direct column maps, FK maps, defaults; provides the `load()` algorithm (read staging → map rows → resolve FKs → bulk insert) |
| `app/Services/ProductImport/Mapping/ProductMapper.php`                         | Abstract per-product mapper: declares ordered list of TableMappers + truncate order; `load()` truncates domain tables then runs each mapper in a transaction                                                              |
| `app/Services/ProductImport/Mapping/Chinook/ChinookProductMapper.php`          | Chinook concrete product mapper (11 tables)                                                                                                                                                                               |
| `app/Services/ProductImport/Mapping/Chinook/ArtistMapper.php` … (11 files)     | One TableMapper per Chinook domain table                                                                                                                                                                                  |
| `app/Services/ProductImport/Mapping/Northwind/NorthwindProductMapper.php`      | Northwind concrete product mapper (11 of 14 upstream tables)                                                                                                                                                              |
| `app/Services/ProductImport/Mapping/Northwind/CategoryMapper.php` … (11 files) | One TableMapper per Northwind domain table                                                                                                                                                                                |
| `app/Services/ProductImport/Mapping/Pagila/PagilaProductMapper.php`            | Pagila concrete product mapper (15 tables)                                                                                                                                                                                |
| `app/Services/ProductImport/Mapping/Pagila/ActorMapper.php` … (15 files)       | One TableMapper per Pagila domain table                                                                                                                                                                                   |
| `tests/Feature/Import/TransformChinookTest.php`                                | End-to-end transform test: fixture SQL → staging → assert domain rows                                                                                                                                                     |
| `tests/Feature/Import/TransformNorthwindTest.php`                              | Northwind transform test (string PKs, dropped tables)                                                                                                                                                                     |
| `tests/Feature/Import/TransformPagilaTest.php`                                 | Pagila transform test (denormalization, partition collapse, circular FK)                                                                                                                                                  |
| `tests/Feature/Import/SchemaPreservationTest.php`                              | Regression: after import, `product_portfolio_snapshots` view + search projections still exist                                                                                                                             |
| `tests/Fixtures/Sources/chinook/minimal.sql`                                   | Minimal Chinook dump (3 artists, albums, tracks, employees w/ self-ref, customer, invoice)                                                                                                                                |
| `tests/Fixtures/Sources/northwind/minimal.sql`                                 | Minimal Northwind dump                                                                                                                                                                                                    |
| `tests/Fixtures/Sources/pagila/schema-minimal.sql` + `data-minimal.sql`        | Minimal Pagila dumps                                                                                                                                                                                                      |

### Files to Modify

| File                                                                                        | Change                                                                                                          |
| ------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `database/migrations/chinook/2026_07_24_210000_create_chinook_schema_and_tables.php:14`     | Remove `DROP SCHEMA IF EXISTS chinook CASCADE;` from `up()` (keep `CREATE SCHEMA IF NOT EXISTS`; keep `down()`) |
| `database/migrations/northwind/2026_07_24_211000_create_northwind_schema_and_tables.php:14` | Remove `DROP SCHEMA IF EXISTS northwind CASCADE;` from `up()`                                                   |
| `database/migrations/pagila/2026_07_24_212000_create_pagila_schema_and_tables.php:14`       | Remove `DROP SCHEMA IF EXISTS pagila CASCADE;` from `up()`                                                      |
| `app/Services/ProductImport/ChinookImporter.php`                                            | Remove destructive swap; add no-source guard; call `ChinookProductMapper`                                       |
| `app/Services/ProductImport/NorthwindImporter.php`                                          | Same — call `NorthwindProductMapper`                                                                            |
| `app/Services/ProductImport/PagilaImporter.php`                                             | Same — call `PagilaProductMapper`                                                                               |
| `tests/Feature/Import/ImportersTest.php`                                                    | Update assertions: importer now populates domain tables when fixture data present                               |

---

## Task Index

- **Phase A — Defang the CASCADE** (Tasks A0–A3): unblock `/admin`, stop the bleeding
- **Phase B — Transform infrastructure** (Tasks B1–B4): abstract base classes
- **Phase C — Chinook mapper** (Tasks C1–C3): simplest 1:1 product
- **Phase D — Northwind mapper** (Tasks D1–D3): string PKs, dropped tables
- **Phase E — Pagila mapper** (Tasks E1–E4): denormalization, partitions, circular FK
- **Phase F — Wire & verify** (Tasks F1–F3): importer refactor, regression test, gates

---

## Phase A — Defang the CASCADE

### Task A0: Unblock the database

**Files:**

- (DB only — no code)

**Interfaces:**

- Consumes: existing migrations + seeders
- Produces: a working database with the `product_portfolio_snapshots` view present

- [ ] **Step 1: Run fresh migration + seed**

Run:

```bash
php artisan migrate:fresh --seed
```

Expected: all migrations run in timestamp order (`210000`…`213000`); the view is created; operator seeded.

- [ ] **Step 2: Verify the view exists**

Run:

```bash
php artisan tinker --execute 'echo Illuminate\Support\Facades\DB::select("SELECT table_type FROM information_schema.views WHERE table_name = '\''product_portfolio_snapshots'\''")[0]->table_type ?? "MISSING";'
```

Expected output: `VIEW`

- [ ] **Step 3: Verify `/admin` loads**

Open `http://samples-20260717.test/admin` in a browser (log in as the seeded operator). Expected: dashboard renders without the `QueryException`.

### Task A1: Remove CASCADE from the three schema-migration `up()` methods

**Files:**

- Modify: `database/migrations/chinook/2026_07_24_210000_create_chinook_schema_and_tables.php:14`
- Modify: `database/migrations/northwind/2026_07_24_211000_create_northwind_schema_and_tables.php:14`
- Modify: `database/migrations/pagila/2026_07_24_212000_create_pagila_schema_and_tables.php:14`

**Interfaces:**

- Consumes: nothing
- Produces: schema migrations whose `up()` no longer destroys cross-schema dependents

- [ ] **Step 1: Remove the CASCADE line from each `up()`**

In each of the three files, delete this single line from `up()` (leave `CREATE SCHEMA IF NOT EXISTS <x>;` on the next line; leave `down()` unchanged — it correctly tears down on rollback/fresh):

```php
DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');   // DELETE THIS LINE
DB::statement('CREATE SCHEMA IF NOT EXISTS chinook;');      // keep
```

Repeat for `northwind` and `pagila`.

- [ ] **Step 2: Verify a fresh migrate still works**

```bash
php artisan migrate:fresh --seed
php artisan tinker --execute 'echo Illuminate\Support\Facades\DB::select("SELECT table_type FROM information_schema.views WHERE table_name = '\''product_portfolio_snapshots'\''")[0]->table_type ?? "MISSING";'
```

Expected: `VIEW` (the view is created by `213000` after the schemas exist; removing the DROP doesn't break creation because `CREATE SCHEMA IF NOT EXISTS` is idempotent and `down()` still drops the schema for fresh runs).

- [ ] **Step 3: Run the schema-related tests**

```bash
php artisan test --compact --filter=Portfolio
```

Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add database/migrations/chinook/2026_07_24_210000_create_chinook_schema_and_tables.php \
        database/migrations/northwind/2026_07_24_211000_create_northwind_schema_and_tables.php \
        database/migrations/pagila/2026_07_24_212000_create_pagila_schema_and_tables.php
git commit -m "fix: remove DROP SCHEMA CASCADE from schema-migration up() to stop destroying cross-schema view and search projections"
```

### Task A2: Add the schema-preservation regression test (red)

**Files:**

- Create: `tests/Feature/Import/SchemaPreservationTest.php`

**Interfaces:**

- Consumes: `ChinookImporter`, `NorthwindImporter`, `PagilaImporter` (current, still-destructive state)
- Produces: a failing test that encodes the invariant "import must not destroy the view or search projections"

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Services\ProductImport\ChinookImporter;
use App\Services\ProductImport\NorthwindImporter;
use App\Services\ProductImport\PagilaImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function assertViewExists(string $name): void
{
    $exists = DB::table('information_schema.views')
        ->where('table_name', $name)
        ->exists();

    expect($exists)->toBeTrue("Expected view [{$name}] to exist after import.");
}

function assertTableExists(string $schema, string $table): void
{
    $exists = DB::table('information_schema.tables')
        ->where('table_schema', $schema)
        ->where('table_name', $table)
        ->exists();

    expect($exists)->toBeTrue("Expected table [{$schema}.{$table}] to exist after import.");
}

it('preserves the product_portfolio_snapshots view after a chinook import', function () {
    app(ChinookImporter::class)->import(dryRun: false);

    assertViewExists('product_portfolio_snapshots');
    assertTableExists('chinook', 'search_projections');
});

it('preserves the product_portfolio_snapshots view after a northwind import', function () {
    app(NorthwindImporter::class)->import(dryRun: false);

    assertViewExists('product_portfolio_snapshots');
    assertTableExists('northwind', 'search_projections');
});

it('preserves the product_portfolio_snapshots view after a pagila import', function () {
    app(PagilaImporter::class)->import(dryRun: false);

    assertViewExists('product_portfolio_snapshots');
    assertTableExists('pagila', 'search_projections');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=SchemaPreservation
```

Expected: FAIL — the current importers run `DROP SCHEMA … CASCADE` (or will, once source files are present), destroying the view. (Note: with no source file cached, the current importer still runs the destructive swap with an empty staging schema, so the view IS destroyed — test should fail on the first assertion.)

- [ ] **Step 3: Commit (red)**

```bash
git add tests/Feature/Import/SchemaPreservationTest.php
git commit -m "test: add schema-preservation regression test (red) for import cascade bug"
```

### Task A3: Defang the three importers (interim — no transform yet)

This task makes the importers non-destructive immediately. Phase F (Task F1) replaces this interim body with the full transform call. We do the minimal fix now so `/admin` stays green even if an import runs before Phase B–E land.

**Files:**

- Modify: `app/Services/ProductImport/ChinookImporter.php`
- Modify: `app/Services/ProductImport/NorthwindImporter.php`
- Modify: `app/Services/ProductImport/PagilaImporter.php`

**Interfaces:**

- Consumes: nothing new
- Produces: importers that (a) no-op when no source file is cached, (b) load into `_staging` only and never touch the live schema

- [ ] **Step 1: Rewrite `ChinookImporter::import()`**

```php
public function import(bool $dryRun = false, ?ResetRun $run = null): array
{
    if ($dryRun) {
        return ['success' => true];
    }

    $sourceFile = $this->getSourceFilePath();

    if ($sourceFile === null || ! File::exists($sourceFile)) {
        return ['success' => true];
    }

    $stagingSchema = 'chinook_staging';

    try {
        DB::statement('DROP SCHEMA IF EXISTS '.$stagingSchema.' CASCADE;');
        DB::statement('CREATE SCHEMA '.$stagingSchema.';');

        $this->processSourceRows($stagingSchema);

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

Rationale: `chinook_staging` has no cross-schema dependents, so dropping it with CASCADE is safe. The live `chinook` schema, the view, and search projections are never touched.

- [ ] **Step 2: Apply the same shape to `NorthwindImporter`** (staging schema `northwind_staging`, `$this->getSourceFilePath()`)

- [ ] **Step 3: Apply the same shape to `PagilaImporter`** — note Pagila has two source files (`schema_filename`, `data_filename`). Guard on **both** being present:

```php
public function import(bool $dryRun = false, ?ResetRun $run = null): array
{
    if ($dryRun) {
        return ['success' => true];
    }

    $manifest = $this->getManifest();

    if ($manifest === null) {
        return ['success' => true];
    }

    $schemaPath = $this->getSourceFilePath($manifest['schema_filename']);
    $dataPath = $this->getSourceFilePath($manifest['data_filename']);

    if ($schemaPath === null || ! File::exists($schemaPath)
        || $dataPath === null || ! File::exists($dataPath)) {
        return ['success' => true];
    }

    $stagingSchema = 'pagila_staging';

    try {
        DB::statement('DROP SCHEMA IF EXISTS '.$stagingSchema.' CASCADE;');
        DB::statement('CREATE SCHEMA '.$stagingSchema.';');

        $this->pgReader->executeSqlDump($schemaPath, $stagingSchema);
        $this->pgReader->executeSqlDump($dataPath, $stagingSchema);

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

- [ ] **Step 4: Run the schema-preservation test**

```bash
php artisan test --compact --filter=SchemaPreservation
```

Expected: PASS (3 tests) — importers no longer touch the live schema.

- [ ] **Step 5: Run the existing importer tests**

```bash
php artisan test --compact --filter=Import
```

Expected: PASS — the "no source file cached" tests still return `['success' => true]`; the mock-based "processes source rows when file exists" tests still expect `executeSqlDump` to be called (staging load still happens).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/ChinookImporter.php \
        app/Services/ProductImport/NorthwindImporter.php \
        app/Services/ProductImport/PagilaImporter.php
git commit -m "fix: defang importers — load into staging only, never DROP the live schema (fixes view destruction)"
```

---

## Phase B — Transform infrastructure

### Task B1: Verify `SourceIdentityRegistry` is safe to use during a run

**Files:**

- Read-only verification: `app/Services/ProductImport/SourceIdentityRegistry.php`, `app/Models/SourceIdentity.php`

**Interfaces:**

- Consumes: `SourceIdentityRegistry::getOrMint(string $entity, array $sourceKey): string`
- Produces: confirmation that `getOrMint` works during a running `ResetRun` (it does — `SourceIdentity` is a core table without the `BelongsToProductDomain` trait)

- [ ] **Step 1: Confirm `SourceIdentity` has no domain gate**

Read `app/Models/SourceIdentity.php`. Confirm its `use` list contains only `HasUuids` (no `BelongsToProductDomain`). If so, `SourceIdentity::create()` inside `getOrMint` is safe during a run. (This is already true per analysis; this step is a verification checkpoint, not a code change.)

- [ ] **Step 2: Write a focused test proving registry works mid-run**

```php
// tests/Feature/Import/SourceIdentityDuringRunTest.php
<?php

declare(strict_types=1);

use App\Models\ResetRun;
use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can mint a source identity while a reset run is active', function () {
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $registry = app(SourceIdentityRegistry::class);

    $uuid = $registry->getOrMint('chinook.artists', ['artist_id' => 5]);

    expect($uuid)->toBeString()
        ->and($registry->getOrMint('chinook.artists', ['artist_id' => 5]))
        ->toBe($uuid);
});
```

- [ ] **Step 3: Run and commit**

```bash
php artisan test --compact --filter=SourceIdentityDuringRun
git add tests/Feature/Import/SourceIdentityDuringRunTest.php
git commit -m "test: prove SourceIdentityRegistry is safe during an active reset run"
```

### Task B2: `TableMapper` abstract base

**Files:**

- Create: `app/Services/ProductImport/Mapping/TableMapper.php`

**Interfaces:**

- Consumes: `SourceIdentityRegistry` (injected), `Illuminate\Support\Facades\DB`
- Produces: abstract class `App\Services\ProductImport\Mapping\TableMapper` with:
    - `abstract protected function entity(): string` — e.g. `"chinook.artists"`
    - `abstract protected function stagingTable(): string` — e.g. `"chinook_staging.artist"`
    - `abstract protected function domainTable(): string` — e.g. `"chinook.artists"`
    - `abstract protected function sourceKey(): array` — e.g. `['artist_id']`
    - `protected function columns(): array` — source-column => domain-column direct maps, default `[]`
    - `protected function foreignKeys(): array` — domain-column => `['entity' => string, 'source' => string]`, default `[]`
    - `protected function defaults(): array` — domain-column => value, default `[]`
    - `protected function beforeInsert(array $rows, string $stagingSchema): array` — hook, default returns `$rows` unchanged
    - `public function load(string $stagingSchema): int` — returns rows inserted

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Mapping/TableMapperTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

uses(RefreshDatabase::class);

it('maps staging rows to domain rows with resolved UUIDs and FKs', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_staging');
    DB::statement('CREATE TABLE test_staging.artist (artist_id int, name text)');
    DB::statement("INSERT INTO test_staging.artist VALUES (1, 'AC/DC'), (2, 'Metallica')");

    Schema::create('artists', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')
        ->with('test.artists', ['artist_id' => 1])
        ->andReturn('aaaaaaaa-0000-7000-8000-000000000001');
    $registry->shouldReceive('getOrMint')
        ->with('test.artists', ['artist_id' => 2])
        ->andReturn('aaaaaaaa-0000-7000-8000-000000000002');

    $mapper = new class($registry) extends TableMapper
    {
        protected function entity(): string { return 'test.artists'; }
        protected function stagingTable(): string { return 'test_staging.artist'; }
        protected function domainTable(): string { return 'artists'; }
        protected function sourceKey(): array { return ['artist_id']; }
        protected function columns(): array { return ['name' => 'name']; }
    };

    $count = $mapper->load('test_staging');

    expect($count)->toBe(2)
        ->and(DB::table('artists')->count())->toBe(2)
        ->and(DB::table('artists')->where('name', 'AC/DC')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=TableMapperTest
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement `TableMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Maps rows from an upstream staging table into a UUID-keyed app-domain table.
 *
 * Each row's source primary key is translated to a stable UUIDv7 via
 * SourceIdentityRegistry, and foreign keys are resolved the same way.
 */
abstract class TableMapper
{
    public function __construct(
        protected SourceIdentityRegistry $registry,
    ) {}

    /** Upstream identity, e.g. "chinook.artists". Must match the source_identities.entity CHECK. */
    abstract protected function entity(): string;

    /** Fully-qualified staging table, e.g. "chinook_staging.artist". */
    abstract protected function stagingTable(): string;

    /** Fully-qualified domain table, e.g. "chinook.artists". */
    abstract protected function domainTable(): string;

    /** Source columns forming the upstream PK, e.g. ["artist_id"]. */
    abstract protected function sourceKey(): array;

    /** Direct column maps: source column => domain column. */
    protected function columns(): array
    {
        return [];
    }

    /** FK maps: domain column => ['entity' => string, 'source' => string]. */
    protected function foreignKeys(): array
    {
        return [];
    }

    /** Static defaults applied to every domain row. */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Hook to transform or augment rows before bulk insert.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $stagingSchema
     * @return array<int, array<string, mixed>>
     */
    protected function beforeInsert(array $rows, string $stagingSchema): array
    {
        return $rows;
    }

    /**
     * Read staging, map to domain rows, bulk-insert. Returns rows inserted.
     */
    public function load(string $stagingSchema): int
    {
        $rows = DB::table($this->stagingTable())->get()->all();
        $domain = [];

        foreach ($rows as $row) {
            $sourceKey = [];
            foreach ($this->sourceKey() as $col) {
                $sourceKey[$col] = $row->{$col} ?? null;
            }

            $uuid = $this->registry->getOrMint($this->entity(), $sourceKey);
            $mapped = ['id' => $uuid];

            foreach ($this->columns() as $source => $target) {
                if (property_exists($row, $source)) {
                    $mapped[$target] = $row->{$source};
                }
            }

            foreach ($this->foreignKeys() as $target => $fk) {
                $sourceValue = $row->{$fk['source']} ?? null;
                if ($sourceValue !== null) {
                    $mapped[$target] = $this->registry->getOrMint(
                        $fk['entity'],
                        [$fk['source'] => $sourceValue],
                    );
                } else {
                    $mapped[$target] = null;
                }
            }

            foreach ($this->defaults() as $key => $value) {
                $mapped[$key] = $value;
            }

            $domain[] = $mapped;
        }

        $domain = $this->beforeInsert($domain, $stagingSchema);

        if ($domain !== []) {
            $now = now();
            foreach ($domain as &$row) {
                $row['created_at'] ??= $now;
                $row['updated_at'] ??= $now;
            }
            unset($row);

            DB::table($this->domainTable())->insert($domain);
        }

        return count($domain);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --compact --filter=TableMapperTest
```

Expected: PASS

- [ ] **Step 5: Add an FK-resolution test**

```php
// append to tests/Unit/Mapping/TableMapperTest.php
it('resolves foreign keys via the registry', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_staging');
    DB::statement('CREATE TABLE test_staging.album (album_id int, title text, artist_id int)');
    DB::statement("INSERT INTO test_staging.album VALUES (10, 'Back in Black', 1)");

    Schema::create('albums', function ($t) {
        $t->uuid('id')->primary();
        $t->string('title');
        $t->uuid('artist_id');
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')
        ->with('test.albums', ['album_id' => 10])
        ->andReturn('bbbbbbbb-0000-7000-8000-000000000010');
    $registry->shouldReceive('getOrMint')
        ->with('test.artists', ['artist_id' => 1])
        ->andReturn('aaaaaaaa-0000-7000-8000-000000000001');

    $mapper = new class($registry) extends TableMapper
    {
        protected function entity(): string { return 'test.albums'; }
        protected function stagingTable(): string { return 'test_staging.album'; }
        protected function domainTable(): string { return 'albums'; }
        protected function sourceKey(): array { return ['album_id']; }
        protected function columns(): array { return ['title' => 'title']; }
        protected function foreignKeys(): array {
            return ['artist_id' => ['entity' => 'test.artists', 'source' => 'artist_id']];
        }
    };

    $mapper->load('test_staging');

    $album = DB::table('albums')->first();
    expect($album->artist_id)->toBe('aaaaaaaa-0000-7000-8000-000000000001');
});
```

- [ ] **Step 6: Run + commit**

```bash
php artisan test --compact --filter=TableMapperTest
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/TableMapper.php tests/Unit/Mapping/TableMapperTest.php
git commit -m "feat: add TableMapper abstract base for upstream→UUID row transform"
```

### Task B3: `SelfReferentialMapper` — two-pass self-FK resolution

**Files:**

- Create: `app/Services/ProductImport/Mapping/SelfReferentialMapper.php`

**Interfaces:**

- Consumes: `TableMapper`
- Produces: abstract subclass that overrides `load()` to insert with the self-FK column nulled, then `UPDATE` the resolved values in a second pass. Subclasses declare `selfReference(): array` → `['column' => 'reports_to', 'entity' => 'chinook.employees', 'source' => 'reports_to']`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Mapping/SelfReferentialMapperTest.php
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
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_staging');
    DB::statement('CREATE TABLE test_staging.employee (employee_id int, name text, reports_to int)');
    DB::statement("INSERT INTO test_staging.employee VALUES (1, 'Boss', NULL), (2, 'Grunt', 1)");

    Schema::create('employees', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->uuid('reports_to')->nullable();
        $t->timestamps();
    });

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->with('test.employees', ['employee_id' => 1])->andReturn('eeee-0001');
    $registry->shouldReceive('getOrMint')->with('test.employees', ['employee_id' => 2])->andReturn('eeee-0002');

    $mapper = new class($registry) extends SelfReferentialMapper
    {
        protected function entity(): string { return 'test.employees'; }
        protected function stagingTable(): string { return 'test_staging.employee'; }
        protected function domainTable(): string { return 'employees'; }
        protected function sourceKey(): array { return ['employee_id']; }
        protected function columns(): array { return ['name' => 'name']; }
        protected function selfReference(): array {
            return ['column' => 'reports_to', 'entity' => 'test.employees', 'source' => 'employee_id'];
        }
    };

    $mapper->load('test_staging');

    $boss = DB::table('employees')->where('name', 'Boss')->first();
    $grunt = DB::table('employees')->where('name', 'Grunt')->first();
    expect($boss->reports_to)->toBeNull()
        ->and($grunt->reports_to)->toBe('eeee-0001');
});
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact --filter=SelfReferentialMapperTest
```

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
     *   - column: the domain self-FK column (e.g. "reports_to")
     *   - entity: the registry entity (e.g. "chinook.employees")
     *   - source: the upstream PK column used to look up the referenced row
     */
    abstract protected function selfReference(): array;

    public function load(string $stagingSchema): int
    {
        $ref = $this->selfReference();
        $rows = DB::table($this->stagingTable())->get()->all();

        /** @var array<int, array<string, mixed>> $domain */
        $domain = [];
        /** @var array<string, array{uuid: string, target: string|null}> $bySourceId source id => domain row */
        $bySourceId = [];

        foreach ($rows as $row) {
            $sourceKey = [];
            foreach ($this->sourceKey() as $col) {
                $sourceKey[$col] = $row->{$col} ?? null;
            }

            $uuid = $this->registry->getOrMint($this->entity(), $sourceKey);
            $mapped = ['id' => $uuid];

            foreach ($this->columns() as $source => $target) {
                if (property_exists($row, $source)) {
                    $mapped[$target] = $row->{$source};
                }
            }

            foreach ($this->foreignKeys() as $target => $fk) {
                $sourceValue = $row->{$fk['source']} ?? null;
                $mapped[$target] = $sourceValue !== null
                    ? $this->registry->getOrMint($fk['entity'], [$fk['source'] => $sourceValue])
                    : null;
            }

            foreach ($this->defaults() as $key => $value) {
                $mapped[$key] = $value;
            }

            // Defer the self-reference column — set null now, resolve after insert
            $mapped[$ref['column']] = null;

            $domain[] = $mapped;
            $pkCol = $this->sourceKey()[0];
            $bySourceId[(string) $row->{$pkCol}] = [
                'uuid' => $uuid,
                'target' => isset($row->{$ref['column']}) && $row->{$ref['column']} !== null
                    ? (string) $row->{$ref['column']}
                    : null,
            ];
        }

        $domain = $this->beforeInsert($domain, $stagingSchema);

        if ($domain !== []) {
            $now = now();
            foreach ($domain as &$row) {
                $row['created_at'] ??= $now;
                $row['updated_at'] ??= $now;
            }
            unset($row);

            DB::table($this->domainTable())->insert($domain);
        }

        // Second pass: resolve self-FK
        foreach ($bySourceId as $entry) {
            if ($entry['target'] !== null && isset($bySourceId[$entry['target']])) {
                DB::table($this->domainTable())
                    ->where('id', $entry['uuid'])
                    ->update([$ref['column'] => $bySourceId[$entry['target']]['uuid']]);
            }
        }

        return count($domain);
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact --filter=SelfReferentialMapperTest
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/SelfReferentialMapper.php tests/Unit/Mapping/SelfReferentialMapperTest.php
git commit -m "feat: add SelfReferentialMapper for two-pass self-FK resolution (employees.reports_to)"
```

### Task B4: `ProductMapper` abstract base

**Files:**

- Create: `app/Services/ProductImport/Mapping/ProductMapper.php`

**Interfaces:**

- Consumes: a `SourceIdentityRegistry` and a list of `TableMapper` instances (declared by subclass via `mappers()`)
- Produces: `load(string $stagingSchema): array{tables: int, rows: int}` — truncates domain tables in reverse-FK order, then runs each mapper in `mappers()` order inside a DB transaction

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Mapping/ProductMapperTest.php
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

it('truncates domain tables then runs each mapper in order inside a transaction', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS test_staging');
    DB::statement('CREATE TABLE test_staging.artist (artist_id int, name text)');
    DB::statement("INSERT INTO test_staging.artist VALUES (1, 'X')");

    Schema::create('artists', function ($t) {
        $t->uuid('id')->primary();
        $t->string('name');
        $t->timestamps();
    });
    DB::table('artists')->insert(['id' => 'old', 'name' => 'stale', 'created_at' => now(), 'updated_at' => now()]);

    $registry = Mockery::mock(SourceIdentityRegistry::class);
    $registry->shouldReceive('getOrMint')->andReturn('new-uuid');

    $artistMapper = new class($registry) extends TableMapper
    {
        protected function entity(): string { return 'test.artists'; }
        protected function stagingTable(): string { return 'test_staging.artist'; }
        protected function domainTable(): string { return 'artists'; }
        protected function sourceKey(): array { return ['artist_id']; }
        protected function columns(): array { return ['name' => 'name']; }
    };

    $productMapper = new class($registry, [$artistMapper]) extends ProductMapper
    {
        public function __construct(
            SourceIdentityRegistry $registry,
            private array $mappersInstance,
        ) {
            parent::__construct($registry);
        }

        protected function mappers(): array { return $this->mappersInstance; }
        protected function truncateOrder(): array { return ['artists']; }
    };

    $result = $productMapper->load('test_staging');

    expect($result['tables'])->toBe(1)
        ->and($result['rows'])->toBe(1)
        ->and(DB::table('artists')->count())->toBe(1)
        ->and(DB::table('artists')->first()->name)->toBe('X');
});
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact --filter=ProductMapperTest
```

Expected: FAIL.

- [ ] **Step 3: Implement `ProductMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use App\Services\ProductImport\SourceIdentityRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates loading all tables for one product: truncate domain tables
 * in reverse-FK order, then run each TableMapper in dependency order.
 */
abstract class ProductMapper
{
    public function __construct(
        protected SourceIdentityRegistry $registry,
    ) {}

    /** @return array<int, TableMapper> in dependency (insert) order */
    abstract protected function mappers(): array;

    /** @return array<int, string> fully-qualified domain tables in reverse-FK (truncate) order */
    abstract protected function truncateOrder(): array;

    /**
     * @return array{tables: int, rows: int}
     */
    public function load(string $stagingSchema): array
    {
        $rows = 0;

        DB::transaction(function () use ($stagingSchema, &$rows) {
            foreach ($this->truncateOrder() as $table) {
                DB::table($table)->truncate();
            }

            foreach ($this->mappers() as $mapper) {
                $rows += $mapper->load($stagingSchema);
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
git commit -m "feat: add ProductMapper abstract base — truncate + run mappers in a transaction"
```

---

## Phase C — Chinook mapper (1:1, cleanest)

Chinook is the simplest: every upstream table maps 1:1 to an app table, PK is renamed `*_id → id`, FK columns keep the same name. Use this phase to establish the concrete-mapper pattern that Northwind and Pagila follow.

### Task C1: Chinook fixture + transform test (red)

**Files:**

- Create: `tests/Fixtures/Sources/chinook/minimal.sql`
- Create: `tests/Feature/Import/TransformChinookTest.php`

**Interfaces:**

- Consumes: `ChinookProductMapper` (not yet created — Task C2), `PostgresSourceReader`
- Produces: a failing end-to-end test that drives the fixture through staging and asserts domain rows

- [ ] **Step 1: Write the minimal fixture SQL**

The fixture must use upstream's table/column names and `public.` schema (the `PostgresSourceReader` rewrites `public.` → `<staging>.`). Cover: 2 artists, 1 album, 1 genre, 1 media_type, 2 employees (self-ref), 1 customer, 1 track, 1 playlist, 1 playlist_track, 1 invoice, 1 invoice_line.

```sql
-- tests/Fixtures/Sources/chinook/minimal.sql
CREATE TABLE public.artist (artist_id INT, name VARCHAR(120));
CREATE TABLE public.album (album_id INT, title VARCHAR(160), artist_id INT);
CREATE TABLE public.genre (genre_id INT, name VARCHAR(120));
CREATE TABLE public.media_type (media_type_id INT, name VARCHAR(120));
CREATE TABLE public.employee (employee_id INT, last_name VARCHAR(20), first_name VARCHAR(20), title VARCHAR(30), reports_to INT, birth_date TIMESTAMP, hire_date TIMESTAMP, address VARCHAR(70), city VARCHAR(40), state VARCHAR(40), country VARCHAR(40), postal_code VARCHAR(10), phone VARCHAR(24), fax VARCHAR(24), email VARCHAR(60));
CREATE TABLE public.customer (customer_id INT, first_name VARCHAR(40), last_name VARCHAR(20), company VARCHAR(80), address VARCHAR(70), city VARCHAR(40), state VARCHAR(40), country VARCHAR(40), postal_code VARCHAR(10), phone VARCHAR(24), fax VARCHAR(24), email VARCHAR(60), support_rep_id INT);
CREATE TABLE public.track (track_id INT, name VARCHAR(200), album_id INT, media_type_id INT, genre_id INT, composer VARCHAR(220), milliseconds INT, bytes INT, unit_price NUMERIC(10,2));
CREATE TABLE public.playlist (playlist_id INT, name VARCHAR(120));
CREATE TABLE public.playlist_track (playlist_id INT, track_id INT);
CREATE TABLE public.invoice (invoice_id INT, customer_id INT, invoice_date TIMESTAMP, billing_address VARCHAR(70), billing_city VARCHAR(40), billing_state VARCHAR(40), billing_country VARCHAR(40), billing_postal_code VARCHAR(10), total NUMERIC(10,2));
CREATE TABLE public.invoice_line (invoice_line_id INT, invoice_id INT, track_id INT, unit_price NUMERIC(10,2), quantity INT);

INSERT INTO public.artist VALUES (1, 'AC/DC'), (2, 'Accept');
INSERT INTO public.album VALUES (1, 'For Those About To Rock We Salute You', 1);
INSERT INTO public.genre VALUES (1, 'Rock');
INSERT INTO public.media_type VALUES (1, 'MPEG audio file');
INSERT INTO public.employee VALUES (1, 'Adams', 'Andrew', 'General Manager', NULL, '1962-02-18', '2002-08-14', '11120 Jasper Ave NW', 'Edmonton', 'AB', 'Canada', 'T5K 2N1', '+1 (780) 428-9482', '+1 (780) 428-9483', 'andrew@chinookcorp.com');
INSERT INTO public.employee VALUES (2, 'Edwards', 'Nancy', 'Sales Manager', 1, '1958-12-08', '2002-05-01', '825 8 Ave SW', 'Calgary', 'AB', 'Canada', 'T2P 2T3', '+1 (403) 262-3443', '+1 (403) 262-3322', 'nancy@chinookcorp.com');
INSERT INTO public.customer VALUES (1, 'Luís', 'Gonçalves', 'Embraer - Empresa Brasileira de Aeronáutica S.A.', 'Av. Brigadeiro Faria Lima, 2170', 'São José dos Campos', 'SP', 'Brazil', '12227-000', '+55 (12) 3923-5555', '+55 (12) 3923-5566', 'luisg@embraer.com.br', 2);
INSERT INTO public.track VALUES (1, 'For Those About To Rock (We Salute You)', 1, 1, 1, 'Angus Young, Malcolm Young, Brian Johnson', 343719, 11170344, 0.99);
INSERT INTO public.playlist VALUES (1, 'Music');
INSERT INTO public.playlist_track VALUES (1, 1);
INSERT INTO public.invoice VALUES (1, 1, '2009-01-01 00:00:00', 'Av. Brigadeiro Faria Lima, 2170', 'São José dos Campos', 'SP', 'Brazil', '12227-000', 1.98);
INSERT INTO public.invoice_line VALUES (1, 1, 1, 0.99, 1);
```

- [ ] **Step 2: Write the failing end-to-end test**

```php
// tests/Feature/Import/TransformChinookTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function loadChinookFixtureIntoStaging(string $stagingSchema): void
{
    DB::statement("DROP SCHEMA IF EXISTS {$stagingSchema} CASCADE");
    DB::statement("CREATE SCHEMA {$stagingSchema}");
    app(PostgresSourceReader::class)->executeSqlDump(
        base_path('tests/Fixtures/Sources/chinook/minimal.sql'),
        $stagingSchema,
    );
}

it('transforms chinook staging rows into the chinook domain schema with UUID PKs', function () {
    loadChinookFixtureIntoStaging('chinook_staging');

    app(ChinookProductMapper::class)->load('chinook_staging');

    expect(DB::table('chinook.artists')->count())->toBe(2)
        ->and(DB::table('chinook.albums')->count())->toBe(1)
        ->and(DB::table('chinook.employees')->count())->toBe(2)
        ->and(DB::table('chinook.tracks')->count())->toBe(1)
        ->and(DB::table('chinook.invoices')->count())->toBe(1)
        ->and(DB::table('chinook.invoice_lines')->count())->toBe(1);
});

it('resolves the employees self-reference reports_to', function () {
    loadChinookFixtureIntoStaging('chinook_staging');
    app(ChinookProductMapper::class)->load('chinook_staging');

    $nancy = DB::table('chinook.employees')->where('first_name', 'Nancy')->first();
    $andrew = DB::table('chinook.employees')->where('first_name', 'Andrew')->first();

    expect($nancy->reports_to)->toBe($andrew->id);
});

it('resolves foreign keys across tables using stable UUIDs', function () {
    loadChinookFixtureIntoStaging('chinook_staging');
    app(ChinookProductMapper::class)->load('chinook_staging');

    $track = DB::table('chinook.tracks')->first();
    $album = DB::table('chinook.albums')->first();
    $genre = DB::table('chinook.genres')->first();
    $mediaType = DB::table('chinook.media_types')->first();

    expect($track->album_id)->toBe($album->id)
        ->and($track->genre_id)->toBe($genre->id)
        ->and($track->media_type_id)->toBe($mediaType->id);
});

it('is idempotent — re-running produces the same UUIDs', function () {
    loadChinookFixtureIntoStaging('chinook_staging');
    app(ChinookProductMapper::class)->load('chinook_staging');
    $firstArtistId = DB::table('chinook.artists')->where('name', 'AC/DC')->first()->id;

    loadChinookFixtureIntoStaging('chinook_staging');
    app(ChinookProductMapper::class)->load('chinook_staging');
    $secondArtistId = DB::table('chinook.artists')->where('name', 'AC/DC')->first()->id;

    expect($secondArtistId)->toBe($firstArtistId);
});
```

- [ ] **Step 3: Run to verify it fails**

```bash
php artisan test --compact --filter=TransformChinook
```

Expected: FAIL — `ChinookProductMapper` not found.

- [ ] **Step 4: Commit (red)**

```bash
git add tests/Fixtures/Sources/chinook/minimal.sql tests/Feature/Import/TransformChinookTest.php
git commit -m "test: add chinook transform fixture and end-to-end tests (red)"
```

### Task C2: Implement the Chinook mapper family

**Files:**

- Create: `app/Services/ProductImport/Mapping/Chinook/ChinookProductMapper.php`
- Create: 11 TableMappers in `app/Services/ProductImport/Mapping/Chinook/`

**Interfaces:**

- Consumes: `TableMapper`, `SelfReferentialMapper`, `ProductMapper`, `SourceIdentityRegistry`
- Produces: `App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper` with `load(string $stagingSchema): array{tables: int, rows: int}`

Each Chinook TableMapper follows the same minimal shape. Here is the **complete `ArtistMapper`** (the canonical example) and a **spec table** for the remaining 10:

- [ ] **Step 1: Create `ArtistMapper`**

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Chinook;

use App\Services\ProductImport\Mapping\TableMapper;

final class ArtistMapper extends TableMapper
{
    protected function entity(): string { return 'chinook.artists'; }
    protected function stagingTable(): string { return 'chinook_staging.artist'; }
    protected function domainTable(): string { return 'chinook.artists'; }
    protected function sourceKey(): array { return ['artist_id']; }
    protected function columns(): array { return ['name' => 'name']; }
}
```

- [ ] **Step 2: Create the remaining 10 mappers per this spec table**

| Mapper file               | entity                   | stagingTable                     | domainTable              | sourceKey                    | columns (source=>target)                                                                                                                                                                                                                 | foreignKeys (target=>[entity,source])                                                                                             | extends                                                                                                                    |
| ------------------------- | ------------------------ | -------------------------------- | ------------------------ | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `AlbumMapper.php`         | `chinook.albums`         | `chinook_staging.album`          | `chinook.albums`         | `['album_id']`               | `title=>title`                                                                                                                                                                                                                           | `artist_id=>[chinook.artists, artist_id]`                                                                                         | `TableMapper`                                                                                                              |
| `GenreMapper.php`         | `chinook.genres`         | `chinook_staging.genre`          | `chinook.genres`         | `['genre_id']`               | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `MediaTypeMapper.php`     | `chinook.media_types`    | `chinook_staging.media_type`     | `chinook.media_types`    | `['media_type_id']`          | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `EmployeeMapper.php`      | `chinook.employees`      | `chinook_staging.employee`       | `chinook.employees`      | `['employee_id']`            | `last_name=>last_name, first_name=>first_name, title=>title, birth_date=>birth_date, hire_date=>hire_date, address=>address, city=>city, state=>state, country=>country, postal_code=>postal_code, phone=>phone, fax=>fax, email=>email` | `support_rep_id`-N/A (this is the self-ref)                                                                                       | `SelfReferentialMapper`, `selfReference(): ['column'=>'reports_to','entity'=>'chinook.employees','source'=>'employee_id']` |
| `CustomerMapper.php`      | `chinook.customers`      | `chinook_staging.customer`       | `chinook.customers`      | `['customer_id']`            | `first_name=>first_name, last_name=>last_name, company=>company, address=>address, city=>city, state=>state, country=>country, postal_code=>postal_code, phone=>phone, fax=>fax, email=>email`                                           | `support_rep_id=>[chinook.employees, support_rep_id]`                                                                             | `TableMapper`                                                                                                              |
| `TrackMapper.php`         | `chinook.tracks`         | `chinook_staging.track`          | `chinook.tracks`         | `['track_id']`               | `name=>name, composer=>composer, milliseconds=>milliseconds, bytes=>bytes, unit_price=>unit_price`                                                                                                                                       | `album_id=>[chinook.albums, album_id], media_type_id=>[chinook.media_types, media_type_id], genre_id=>[chinook.genres, genre_id]` | `TableMapper`                                                                                                              |
| `PlaylistMapper.php`      | `chinook.playlists`      | `chinook_staging.playlist`       | `chinook.playlists`      | `['playlist_id']`            | `name=>name`                                                                                                                                                                                                                             | —                                                                                                                                 | `TableMapper`                                                                                                              |
| `InvoiceMapper.php`       | `chinook.invoices`       | `chinook_staging.invoice`        | `chinook.invoices`       | `['invoice_id']`             | `invoice_date=>invoice_date, billing_address=>billing_address, billing_city=>billing_city, billing_state=>billing_state, billing_country=>billing_country, billing_postal_code=>billing_postal_code, total=>total`                       | `customer_id=>[chinook.customers, customer_id]`                                                                                   | `TableMapper`                                                                                                              |
| `PlaylistTrackMapper.php` | `chinook.playlist_track` | `chinook_staging.playlist_track` | `chinook.playlist_track` | `['playlist_id','track_id']` | — (composite source key → surrogate UUID)                                                                                                                                                                                                | `playlist_id=>[chinook.playlists, playlist_id], track_id=>[chinook.tracks, track_id]`                                             | `TableMapper`                                                                                                              |
| `InvoiceLineMapper.php`   | `chinook.invoice_lines`  | `chinook_staging.invoice_line`   | `chinook.invoice_lines`  | `['invoice_line_id']`        | `unit_price=>unit_price, quantity=>quantity`                                                                                                                                                                                             | `invoice_id=>[chinook.invoices, invoice_id], track_id=>[chinook.tracks, track_id]`                                                | `TableMapper`                                                                                                              |

For composite-key mappers (e.g. `PlaylistTrackMapper`), note that `sourceKey()` returns both columns and `foreignKeys()` resolves each — the surrogate `id` UUID comes from `getOrMint` keyed on the composite `['playlist_id'=>.., 'track_id'=>..]`.

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
            'chinook.invoice_lines',
            'chinook.playlist_track',
            'chinook.invoices',
            'chinook.playlists',
            'chinook.tracks',
            'chinook.customers',
            'chinook.albums',
            'chinook.employees',
            'chinook.media_types',
            'chinook.genres',
            'chinook.artists',
        ];
    }
}
```

- [ ] **Step 4: Run the transform tests**

```bash
php artisan test --compact --filter=TransformChinook
```

Expected: PASS (4 tests).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/Chinook/
git commit -m "feat: implement Chinook mapper family (11 tables) with upstream→UUID transform"
```

### Task C3: Verify search-projection triggers fire on transformed inserts

**Files:**

- Read-only verification (the triggers exist from `210001`)

- [ ] **Step 1: Write a verification test**

```php
// tests/Feature/Import/ChinookSearchProjectionTriggerTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('populates chinook.search_projections via triggers when the transform inserts artists', function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
    DB::statement('CREATE SCHEMA chinook_staging');
    app(PostgresSourceReader::class)->executeSqlDump(
        base_path('tests/Fixtures/Sources/chinook/minimal.sql'),
        'chinook_staging',
    );

    app(ChinookProductMapper::class)->load('chinook_staging');

    expect(DB::table('chinook.search_projections')->where('entity_type', 'artist')->count())->toBe(2);
});
```

- [ ] **Step 2: Run + commit**

```bash
php artisan test --compact --filter=ChinookSearchProjectionTrigger
git add tests/Feature/Import/ChinookSearchProjectionTriggerTest.php
git commit -m "test: verify chinook search-projection triggers fire on transformed inserts"
```

---

## Phase D — Northwind mapper (string PKs, dropped tables)

Northwind differences from Chinook: (1) some PKs are strings (`customers.customer_id` = "ALFKI", `territories.territory_id` = "10038"), (2) three upstream tables have no app counterpart and are omitted, (3) `products.discontinued` is `integer` upstream → `boolean` app.

### Task D1: Northwind fixture + transform test (red)

**Files:**

- Create: `tests/Fixtures/Sources/northwind/minimal.sql`
- Create: `tests/Feature/Import/TransformNorthwindTest.php`

- [ ] **Step 1: Write the fixture** (2 categories, 1 customer [string PK "ALFKI"], 1 employee [self-ref], 1 region, 1 territory [string PK], 1 shipper, 1 supplier, 1 product, 1 order, 1 order_detail junction). Omit `customer_demographics`, `customer_customer_demo`, `us_states`. Use the upstream column names from the schema analysis: `customers(customer_id varchar(5), company_name, ...)`, `territories(territory_id varchar(20), territory_description, region_id)`.

```sql
-- tests/Fixtures/Sources/northwind/minimal.sql
CREATE TABLE public.categories (category_id SMALLINT, category_name VARCHAR(15), description TEXT, picture BYTEA);
CREATE TABLE public.customers (customer_id VARCHAR(5), company_name VARCHAR(40), contact_name VARCHAR(30), contact_title VARCHAR(30), address VARCHAR(60), city VARCHAR(15), region VARCHAR(15), postal_code VARCHAR(10), country VARCHAR(15), phone VARCHAR(24), fax VARCHAR(24));
CREATE TABLE public.employees (employee_id SMALLINT, last_name VARCHAR(20), first_name VARCHAR(10), title VARCHAR(30), title_of_courtesy VARCHAR(25), birth_date DATE, hire_date DATE, address VARCHAR(60), city VARCHAR(15), region VARCHAR(15), postal_code VARCHAR(10), country VARCHAR(15), home_phone VARCHAR(24), extension VARCHAR(4), photo BYTEA, notes TEXT, reports_to SMALLINT, photo_path VARCHAR(255));
CREATE TABLE public.region (region_id SMALLINT, region_description VARCHAR(60));
CREATE TABLE public.territories (territory_id VARCHAR(20), territory_description VARCHAR(60), region_id SMALLINT);
CREATE TABLE public.employee_territories (employee_id SMALLINT, territory_id VARCHAR(20));
CREATE TABLE public.shippers (shipper_id SMALLINT, company_name VARCHAR(40), phone VARCHAR(24));
CREATE TABLE public.suppliers (supplier_id SMALLINT, company_name VARCHAR(40), contact_name VARCHAR(30), contact_title VARCHAR(30), address VARCHAR(60), city VARCHAR(15), region VARCHAR(15), postal_code VARCHAR(10), country VARCHAR(15), phone VARCHAR(24), fax VARCHAR(24), homepage TEXT);
CREATE TABLE public.products (product_id SMALLINT, product_name VARCHAR(40), supplier_id SMALLINT, category_id SMALLINT, quantity_per_unit VARCHAR(20), unit_price REAL, units_in_stock SMALLINT, units_on_order SMALLINT, reorder_level SMALLINT, discontinued INT);
CREATE TABLE public.orders (order_id SMALLINT, customer_id VARCHAR(5), employee_id SMALLINT, order_date DATE, required_date DATE, shipped_date DATE, ship_via SMALLINT, freight REAL, ship_name VARCHAR(40), ship_address VARCHAR(60), ship_city VARCHAR(15), ship_region VARCHAR(15), ship_postal_code VARCHAR(10), ship_country VARCHAR(15));
CREATE TABLE public.order_details (order_id SMALLINT, product_id SMALLINT, unit_price REAL, quantity SMALLINT, discount REAL);

INSERT INTO public.categories VALUES (1, 'Beverages', 'Soft drinks, coffees, teas, beers, and ales', NULL);
INSERT INTO public.customers VALUES ('ALFKI', 'Alfreds Futterkiste', 'Maria Anders', 'Sales Representative', 'Obere Str. 57', 'Berlin', NULL, '12209', 'Germany', '030-0074321', '030-0076545');
INSERT INTO public.employees VALUES (1, 'Davolio', 'Nancy', 'Sales Representative', 'Ms.', '1968-12-08', '1992-05-01', '507 - 20th Ave. E. Apt. 2A', 'Seattle', 'WA', '98122', 'USA', '(206) 555-9857', '5467', NULL, 'Education includes a BA.', NULL, NULL);
INSERT INTO public.employees VALUES (2, 'Fuller', 'Andrew', 'Vice President, Sales', 'Dr.', '1952-02-19', '1992-08-14', '908 W. Capital Way', 'Tacoma', 'WA', '98401', 'USA', '(206) 555-9482', '3457', NULL, 'Andrew received his BTS commercial.', 1, NULL);
INSERT INTO public.region VALUES (1, 'Eastern');
INSERT INTO public.territories VALUES ('10038', 'New York', 1);
INSERT INTO public.employee_territories VALUES (1, '10038');
INSERT INTO public.shippers VALUES (1, 'Speedy Express', '(503) 555-9831');
INSERT INTO public.suppliers VALUES (1, 'Exotic Liquids', 'Charlotte Cooper', 'Purchasing Manager', '49 Gilbert St.', 'London', NULL, 'EC1 4SD', 'UK', '(171) 555-2222', NULL, NULL);
INSERT INTO public.products VALUES (1, 'Chai', 1, 1, '10 boxes x 20 bags', 18.0, 39, 0, 10, 0);
INSERT INTO public.orders VALUES (1, 'ALFKI', 1, '1997-08-25', '1997-09-22', '1997-09-02', 1, 32.38, 'Alfreds Futterkiste', 'Obere Str. 57', 'Berlin', NULL, '12209', 'Germany');
INSERT INTO public.order_details VALUES (1, 1, 18.0, 10, 0.0);
```

- [ ] **Step 2: Write the failing test** (mirror `TransformChinookTest.php` structure; assert counts, the string-PK customer round-trips, the self-ref resolves, `discontinued` is boolean `false`, and the order's `customer_id` FK resolves to the customer whose source PK was "ALFKI"):

```php
// tests/Feature/Import/TransformNorthwindTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Northwind\NorthwindProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function loadNorthwindFixtureIntoStaging(string $stagingSchema): void
{
    DB::statement("DROP SCHEMA IF EXISTS {$stagingSchema} CASCADE");
    DB::statement("CREATE SCHEMA {$stagingSchema}");
    app(PostgresSourceReader::class)->executeSqlDump(
        base_path('tests/Fixtures/Sources/northwind/minimal.sql'),
        $stagingSchema,
    );
}

it('transforms northwind staging rows into the northwind domain schema', function () {
    loadNorthwindFixtureIntoStaging('northwind_staging');
    app(NorthwindProductMapper::class)->load('northwind_staging');

    expect(DB::table('northwind.categories')->count())->toBe(1)
        ->and(DB::table('northwind.customers')->count())->toBe(1)
        ->and(DB::table('northwind.employees')->count())->toBe(2)
        ->and(DB::table('northwind.products')->count())->toBe(1)
        ->and(DB::table('northwind.orders')->count())->toBe(1)
        ->and(DB::table('northwind.order_details')->count())->toBe(1);
});

it('preserves the string customer id mapping across the orders FK', function () {
    loadNorthwindFixtureIntoStaging('northwind_staging');
    app(NorthwindProductMapper::class)->load('northwind_staging');

    $customer = DB::table('northwind.customers')->where('company_name', 'Alfreds Futterkiste')->first();
    $order = DB::table('northwind.orders')->first();

    expect($order->customer_id)->toBe($customer->id);
});

it('casts products.discontinued from integer to boolean', function () {
    loadNorthwindFixtureIntoStaging('northwind_staging');
    app(NorthwindProductMapper::class)->load('northwind_staging');

    $product = DB::table('northwind.products')->where('product_name', 'Chai')->first();
    expect($product->discontinued)->toBeFalse();
});
```

- [ ] **Step 3: Run to verify it fails, commit (red)**

```bash
php artisan test --compact --filter=TransformNorthwind  # FAIL
git add tests/Fixtures/Sources/northwind/minimal.sql tests/Feature/Import/TransformNorthwindTest.php
git commit -m "test: add northwind transform fixture and tests (red)"
```

### Task D2: Implement the Northwind mapper family

**Files:**

- Create: `app/Services/ProductImport/Mapping/Northwind/NorthwindProductMapper.php`
- Create: 11 TableMappers in `app/Services/ProductImport/Mapping/Northwind/`

- [ ] **Step 1: Create the 11 mappers per this spec table**

| Mapper file                   | entity                           | stagingTable                             | domainTable                      | sourceKey                        | columns                                                                                                                                                                                                                                                                                                                                      | foreignKeys                                                                                                                                    | extends / notes                                                                                                              |
| ----------------------------- | -------------------------------- | ---------------------------------------- | -------------------------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `CategoryMapper.php`          | `northwind.categories`           | `northwind_staging.categories`           | `northwind.categories`           | `['category_id']`                | `category_name=>category_name, description=>description, picture=>picture`                                                                                                                                                                                                                                                                   | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `CustomerMapper.php`          | `northwind.customers`            | `northwind_staging.customers`            | `northwind.customers`            | `['customer_id']`                | `company_name=>company_name, contact_name=>contact_name, contact_title=>contact_title, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, phone=>phone, fax=>fax`                                                                                                                                     | —                                                                                                                                              | `TableMapper` (string PK — registry handles it)                                                                              |
| `EmployeeMapper.php`          | `northwind.employees`            | `northwind_staging.employees`            | `northwind.employees`            | `['employee_id']`                | `last_name=>last_name, first_name=>first_name, title=>title, title_of_courtesy=>title_of_courtesy, birth_date=>birth_date, hire_date=>hire_date, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, home_phone=>home_phone, extension=>extension, photo=>photo, notes=>notes, photo_path=>photo_path` | (self-ref)                                                                                                                                     | `SelfReferentialMapper`, `selfReference(): ['column'=>'reports_to','entity'=>'northwind.employees','source'=>'employee_id']` |
| `RegionMapper.php`            | `northwind.regions`              | `northwind_staging.region`               | `northwind.regions`              | `['region_id']`                  | `region_description=>region_description`                                                                                                                                                                                                                                                                                                     | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `TerritoryMapper.php`         | `northwind.territories`          | `northwind_staging.territories`          | `northwind.territories`          | `['territory_id']`               | `territory_description=>territory_description`                                                                                                                                                                                                                                                                                               | `region_id=>[northwind.regions, region_id]`                                                                                                    | `TableMapper` (string PK)                                                                                                    |
| `EmployeeTerritoryMapper.php` | `northwind.employee_territories` | `northwind_staging.employee_territories` | `northwind.employee_territories` | `['employee_id','territory_id']` | —                                                                                                                                                                                                                                                                                                                                            | `employee_id=>[northwind.employees, employee_id], territory_id=>[northwind.territories, territory_id]`                                         | `TableMapper` (junction, composite key, surrogate UUID)                                                                      |
| `ShipperMapper.php`           | `northwind.shippers`             | `northwind_staging.shippers`             | `northwind.shippers`             | `['shipper_id']`                 | `company_name=>company_name, phone=>phone`                                                                                                                                                                                                                                                                                                   | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `SupplierMapper.php`          | `northwind.suppliers`            | `northwind_staging.suppliers`            | `northwind.suppliers`            | `['supplier_id']`                | `company_name=>company_name, contact_name=>contact_name, contact_title=>contact_title, address=>address, city=>city, region=>region, postal_code=>postal_code, country=>country, phone=>phone, fax=>fax, homepage=>homepage`                                                                                                                 | —                                                                                                                                              | `TableMapper`                                                                                                                |
| `ProductMapper.php`           | `northwind.products`             | `northwind_staging.products`             | `northwind.products`             | `['product_id']`                 | `product_name=>product_name, quantity_per_unit=>quantity_per_unit, unit_price=>unit_price, units_in_stock=>units_in_stock, units_on_order=>units_on_order, reorder_level=>reorder_level`                                                                                                                                                     | `supplier_id=>[northwind.suppliers, supplier_id], category_id=>[northwind.categories, category_id]`                                            | `TableMapper` + override `beforeInsert()` to cast `discontinued` int→bool (see example below)                                |
| `OrderMapper.php`             | `northwind.orders`               | `northwind_staging.orders`               | `northwind.orders`               | `['order_id']`                   | `order_date=>order_date, required_date=>required_date, shipped_date=>shipped_date, freight=>freight, ship_name=>ship_name, ship_address=>ship_address, ship_city=>ship_city, ship_region=>ship_region, ship_postal_code=>ship_postal_code, ship_country=>ship_country`                                                                       | `customer_id=>[northwind.customers, customer_id], employee_id=>[northwind.employees, employee_id], ship_via=>[northwind.shippers, shipper_id]` | `TableMapper` (note `ship_via` source → `ship_via` domain FK to shippers)                                                    |
| `OrderDetailMapper.php`       | `northwind.order_details`        | `northwind_staging.order_details`        | `northwind.order_details`        | `['order_id','product_id']`      | `unit_price=>unit_price, quantity=>quantity, discount=>discount`                                                                                                                                                                                                                                                                             | `order_id=>[northwind.orders, order_id], product_id=>[northwind.products, product_id]`                                                         | `TableMapper` (junction, composite key, surrogate UUID)                                                                      |

Example of the `ProductMapper` with the `discontinued` cast:

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Northwind;

use App\Services\ProductImport\Mapping\TableMapper;

final class ProductMapper extends TableMapper
{
    protected function entity(): string { return 'northwind.products'; }
    protected function stagingTable(): string { return 'northwind_staging.products'; }
    protected function domainTable(): string { return 'northwind.products'; }
    protected function sourceKey(): array { return ['product_id']; }
    protected function columns(): array
    {
        return [
            'product_name' => 'product_name', 'quantity_per_unit' => 'quantity_per_unit',
            'unit_price' => 'unit_price', 'units_in_stock' => 'units_in_stock',
            'units_on_order' => 'units_on_order', 'reorder_level' => 'reorder_level',
        ];
    }
    protected function foreignKeys(): array
    {
        return [
            'supplier_id' => ['entity' => 'northwind.suppliers', 'source' => 'supplier_id'],
            'category_id' => ['entity' => 'northwind.categories', 'source' => 'category_id'],
        ];
    }
    protected function beforeInsert(array $rows, string $stagingSchema): array
    {
        foreach ($rows as &$row) {
            $row['discontinued'] = (bool) ($row['discontinued'] ?? 0);
        }

        return $rows;
    }
}
```

- [ ] **Step 2: Create `NorthwindProductMapper`** (mirror `ChinookProductMapper`; `truncateOrder()` in reverse-FK order: order_details, orders, employee_territories, territories, products, regions, shippers, suppliers, employees, customers, categories)

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Northwind;

use App\Services\ProductImport\Mapping\ProductMapper;

final class NorthwindProductMapper extends ProductMapper
{
    protected function mappers(): array
    {
        return [
            new CategoryMapper($this->registry),
            new CustomerMapper($this->registry),
            new EmployeeMapper($this->registry),
            new RegionMapper($this->registry),
            new TerritoryMapper($this->registry),
            new EmployeeTerritoryMapper($this->registry),
            new ShipperMapper($this->registry),
            new SupplierMapper($this->registry),
            new ProductMapper($this->registry),
            new OrderMapper($this->registry),
            new OrderDetailMapper($this->registry),
        ];
    }

    protected function truncateOrder(): array
    {
        return [
            'northwind.order_details',
            'northwind.orders',
            'northwind.employee_territories',
            'northwind.territories',
            'northwind.products',
            'northwind.shippers',
            'northwind.suppliers',
            'northwind.regions',
            'northwind.employees',
            'northwind.customers',
            'northwind.categories',
        ];
    }
}
```

- [ ] **Step 3: Run + commit**

```bash
php artisan test --compact --filter=TransformNorthwind
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/Northwind/
git commit -m "feat: implement Northwind mapper family (11 tables, string PKs, discontinued cast)"
```

### Task D3: Northwind search-projection trigger verification

- [ ] **Step 1: Write + run the trigger test** (mirror Task C3 for `northwind.search_projections`, entity_type `'product'` for the Chai product):

```php
// tests/Feature/Import/NorthwindSearchProjectionTriggerTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Northwind\NorthwindProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('populates northwind.search_projections via triggers when the transform inserts products', function () {
    DB::statement('DROP SCHEMA IF EXISTS northwind_staging CASCADE');
    DB::statement('CREATE SCHEMA northwind_staging');
    app(PostgresSourceReader::class)->executeSqlDump(
        base_path('tests/Fixtures/Sources/northwind/minimal.sql'),
        'northwind_staging',
    );

    app(NorthwindProductMapper::class)->load('northwind_staging');

    expect(DB::table('northwind.search_projections')->where('entity_type', 'product')->count())->toBe(1);
});
```

- [ ] **Step 2: Run + commit**

```bash
php artisan test --compact --filter=NorthwindSearchProjectionTrigger
git add tests/Feature/Import/NorthwindSearchProjectionTriggerTest.php
git commit -m "test: verify northwind search-projection triggers fire on transformed inserts"
```

---

## Phase E — Pagila mapper (denormalization, partitions, circular FK)

Pagila is the hardest: (1) `address` table is normalized upstream but flattened into `staff.address`/`customers.address`/`stores.address` strings in the app; (2) `payment` is split across 7 partitions upstream → one `payments` table; (3) circular FK `staff`↔`stores`.

### Task E1: Pagila fixtures + transform test (red)

**Files:**

- Create: `tests/Fixtures/Sources/pagila/schema-minimal.sql`
- Create: `tests/Fixtures/Sources/pagila/data-minimal.sql`
- Create: `tests/Feature/Import/TransformPagilaTest.php`

- [ ] **Step 1: Write the schema fixture** (upstream column names from analysis: `actor(actor_id, first_name, last_name, last_update)`, `category(category_id, name, last_update)`, `country(country_id, country, last_update)`, `city(city_id, city, country_id, last_update)`, `language(language_id, name, last_update)`, `address(address_id, address, address2, district, city_id, postal_code, phone, last_update)`, `store(store_id, manager_staff_id, address_id, last_update)`, `staff(st staff_id, first_name, last_name, address_id, email, store_id, active, username, password, last_update, picture)`, `customer(customer_id, store_id, first_name, last_name, email, address_id, activebool, create_date, last_update, active)`, `film(film_id, title, description, release_year, language_id, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, last_update, special_features, fulltext)`, `film_actor(actor_id, film_id, last_update)`, `film_category(film_id, category_id, last_update)`, `inventory(inventory_id, film_id, store_id, last_update)`, `rental(rental_id, rental_date, inventory_id, customer_id, return_date, staff_id, last_update)`, `payment(payment_id, customer_id, staff_id, rental_id, amount, payment_date)`).

- [ ] **Step 2: Write the data fixture** (1 country, 1 city, 1 language, 2 actors, 1 category, 1 address [for store+staff+customer], 1 staff, 1 store [manager = staff, circular], 1 customer, 1 film, 1 film_actor, 1 film_category, 1 inventory, 1 rental, 1 payment).

- [ ] **Step 3: Write the failing test** (assert denormalized addresses land in staff/customer/store, the circular FK resolves, payment lands in `pagila.payments`):

```php
// tests/Feature/Import/TransformPagilaTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Pagila\PagilaProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function loadPagilaFixtureIntoStaging(string $stagingSchema): void
{
    DB::statement("DROP SCHEMA IF EXISTS {$stagingSchema} CASCADE");
    DB::statement("CREATE SCHEMA {$stagingSchema}");
    $reader = app(PostgresSourceReader::class);
    $reader->executeSqlDump(base_path('tests/Fixtures/Sources/pagila/schema-minimal.sql'), $stagingSchema);
    $reader->executeSqlDump(base_path('tests/Fixtures/Sources/pagila/data-minimal.sql'), $stagingSchema);
}

it('denormalizes the upstream address into staff/customer/store address strings', function () {
    loadPagilaFixtureIntoStaging('pagila_staging');
    app(PagilaProductMapper::class)->load('pagila_staging');

    $staff = DB::table('pagila.staff')->first();
    expect($staff->address)->toBeString()->not->toBeNull();
});

it('resolves the circular staff↔store FK', function () {
    loadPagilaFixtureIntoStaging('pagila_staging');
    app(PagilaProductMapper::class)->load('pagila_staging');

    $staff = DB::table('pagila.staff')->first();
    $store = DB::table('pagila.stores')->first();

    expect($staff->store_id)->toBe($store->id)
        ->and($store->manager_staff_id)->toBe($staff->id);
});

it('collapses payments into the single pagila.payments table', function () {
    loadPagilaFixtureIntoStaging('pagila_staging');
    app(PagilaProductMapper::class)->load('pagila_staging');

    expect(DB::table('pagila.payments')->count())->toBe(1);
});
```

- [ ] **Step 4: Run to verify it fails, commit (red)**

```bash
php artisan test --compact --filter=TransformPagila  # FAIL
git add tests/Fixtures/Sources/pagila/ tests/Feature/Import/TransformPagilaTest.php
git commit -m "test: add pagila transform fixtures and tests (red)"
```

### Task E2: Implement the Pagila mapper family (15 tables)

**Files:**

- Create: `app/Services/ProductImport/Mapping/Pagila/PagilaProductMapper.php`
- Create: 15 TableMappers

| Mapper file              | stagingTable                          | domainTable              | sourceKey                   | columns                                                                                                                                                                              | foreignKeys / notes                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| ------------------------ | ------------------------------------- | ------------------------ | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `CountryMapper.php`      | `pagila_staging.country`              | `pagila.countries`       | `['country_id']`            | `country=>country`                                                                                                                                                                   | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `CityMapper.php`         | `pagila_staging.city`                 | `pagila.cities`          | `['city_id']`               | `city=>city`                                                                                                                                                                         | `country_id=>[pagila.countries, country_id]`                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `LanguageMapper.php`     | `pagila_staging.language`             | `pagila.languages`       | `['language_id']`           | `name=>name`                                                                                                                                                                         | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `ActorMapper.php`        | `pagila_staging.actor`                | `pagila.actors`          | `['actor_id']`              | `first_name=>first_name, last_name=>last_name`                                                                                                                                       | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `CategoryMapper.php`     | `pagila_staging.category`             | `pagila.categories`      | `['category_id']`           | `name=>name`                                                                                                                                                                         | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `StaffMapper.php`        | `pagila_staging.staff`                | `pagila.staff`           | `['staff_id']`              | `first_name=>first_name, last_name=>last_name, email=>email, active=>active, username=>username, password=>password, picture=>picture`                                               | `store_id=>[pagila.stores, store_id]`. `beforeInsert`: denormalize address via a JOIN to `pagila_staging.address` on `address_id`, set `address` from the upstream address row.                                                                                                                                                                                                                                                                                            |
| `StoreMapper.php`        | `pagila_staging.store`                | `pagila.stores`          | `['store_id']`              | —                                                                                                                                                                                    | `manager_staff_id=>[pagila.staff, staff_id]`. `beforeInsert`: denormalize address (JOIN on `address_id`). **Must run in same transaction as Staff** (circular FK is DEFERRABLE INITIALLY DEFERRED).                                                                                                                                                                                                                                                                        |
| `CustomerMapper.php`     | `pagila_staging.customer`             | `pagila.customers`       | `['customer_id']`           | `first_name=>first_name, last_name=>last_name, email=>email, active=>activebool`                                                                                                     | `store_id=>[pagila.stores, store_id]`. `beforeInsert`: denormalize address.                                                                                                                                                                                                                                                                                                                                                                                                |
| `FilmMapper.php`         | `pagila_staging.film`                 | `pagila.films`           | `['film_id']`               | `title=>title, description=>description, release_year=>release_year, rental_duration=>rental_duration, rental_rate=>rental_rate, length=>length, replacement_cost=>replacement_cost` | `language_id=>[pagila.languages, language_id], original_language_id=>[pagila.languages, original_language_id]`. `beforeInsert`: cast `rating` enum→string (`(string) $row['rating']`), cast `special_features` array→text (`is_array(...) ? implode(',', ...) : (string) ...`).                                                                                                                                                                                            |
| `FilmActorMapper.php`    | `pagila_staging.film_actor`           | `pagila.film_actors`     | `['actor_id','film_id']`    | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], actor_id=>[pagila.actors, actor_id]` (junction, surrogate UUID)                                                                                                                                                                                                                                                                                                                                                                         |
| `FilmCategoryMapper.php` | `pagila_staging.film_category`        | `pagila.film_categories` | `['film_id','category_id']` | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], category_id=>[pagila.categories, category_id]` (junction)                                                                                                                                                                                                                                                                                                                                                                               |
| `FilmTextMapper.php`     | `pagila_staging.film` (re-reads film) | `pagila.film_texts`      | `['film_id']`               | `title=>title, description=>description`                                                                                                                                             | `film_id=>[pagila.films, film_id]`. (Derived from the same film rows — upstream has no `film_text` table in the base schema; app derives it.)                                                                                                                                                                                                                                                                                                                              |
| `InventoryMapper.php`    | `pagila_staging.inventory`            | `pagila.inventories`     | `['inventory_id']`          | —                                                                                                                                                                                    | `film_id=>[pagila.films, film_id], store_id=>[pagila.stores, store_id]`                                                                                                                                                                                                                                                                                                                                                                                                    |
| `RentalMapper.php`       | `pagila_staging.rental`               | `pagila.rentals`         | `['rental_id']`             | `rental_date=>rental_date, return_date=>return_date`                                                                                                                                 | `inventory_id=>[pagila.inventories, inventory_id], customer_id=>[pagila.customers, customer_id], staff_id=>[pagila.staff, staff_id]`                                                                                                                                                                                                                                                                                                                                       |
| `PaymentMapper.php`      | `pagila_staging.payment`              | `pagila.payments`        | `['payment_id']`            | `amount=>amount, payment_date=>payment_date`                                                                                                                                         | `customer_id=>[pagila.customers, customer_id], staff_id=>[pagila.staff, staff_id], rental_id=>[pagila.rentals, rental_id]`. **Partition collapse**: the staging schema flattens all `payment_p2022_*` partitions into a single `payment` table (the `PostgresSourceReader` loads each partition's INSERTs rewritten to `payment`). If partitions survive as separate staging tables, override `stagingTable()` to use a UNION ALL subquery or pre-merge in `beforeInsert`. |

Example of the `StaffMapper` address denormalization:

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Pagila;

use App\Services\ProductImport\Mapping\TableMapper;
use Illuminate\Support\Facades\DB;

final class StaffMapper extends TableMapper
{
    protected function entity(): string { return 'pagila.staff'; }
    protected function stagingTable(): string { return 'pagila_staging.staff'; }
    protected function domainTable(): string { return 'pagila.staff'; }
    protected function sourceKey(): array { return ['staff_id']; }
    protected function columns(): array
    {
        return [
            'first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email',
            'active' => 'active', 'username' => 'username', 'password' => 'password', 'picture' => 'picture',
            'address_id' => 'address_source_id',
        ];
    }
    protected function foreignKeys(): array
    {
        return ['store_id' => ['entity' => 'pagila.stores', 'source' => 'store_id']];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $stagingSchema
     * @return array<int, array<string, mixed>>
     */
    protected function beforeInsert(array $rows, string $stagingSchema): array
    {
        $addresses = DB::table("{$stagingSchema}.address")->get()->keyBy('address_id');

        foreach ($rows as &$row) {
            $addrId = $row['address_source_id'] ?? null;
            $row['address'] = $addrId !== null && $addresses->has($addrId)
                ? $addresses[$addrId]->address
                : null;
            unset($row['address_source_id']);
        }

        return $rows;
    }
}
```

> **Note on address source:** add `address_id => 'address_source_id'` to `columns()` so the upstream `address_id` flows into the row under a temporary key, then resolve/unset it in `beforeInsert`. Apply the same pattern to `StoreMapper` and `CustomerMapper`.

- [ ] **Step 3: Create `PagilaProductMapper`** with `truncateOrder()` in reverse-FK order (payments, rentals, inventories, film_texts, film_categories, film_actors, films, customers, stores, staff, categories, actors, languages, cities, countries). **Staff and Stores must be adjacent and inside the transaction** (circular FK). Insert order: countries → cities → languages → actors → categories → staff → stores → customers → films → film_actors → film_categories → film_texts → inventories → rentals → payments.

```php
<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Pagila;

use App\Services\ProductImport\Mapping\ProductMapper;

final class PagilaProductMapper extends ProductMapper
{
    protected function mappers(): array
    {
        return [
            new CountryMapper($this->registry),
            new CityMapper($this->registry),
            new LanguageMapper($this->registry),
            new ActorMapper($this->registry),
            new CategoryMapper($this->registry),
            new StaffMapper($this->registry),
            new StoreMapper($this->registry),
            new CustomerMapper($this->registry),
            new FilmMapper($this->registry),
            new FilmActorMapper($this->registry),
            new FilmCategoryMapper($this->registry),
            new FilmTextMapper($this->registry),
            new InventoryMapper($this->registry),
            new RentalMapper($this->registry),
            new PaymentMapper($this->registry),
        ];
    }

    protected function truncateOrder(): array
    {
        return [
            'pagila.payments',
            'pagila.rentals',
            'pagila.inventories',
            'pagila.film_texts',
            'pagila.film_categories',
            'pagila.film_actors',
            'pagila.films',
            'pagila.customers',
            'pagila.stores',
            'pagila.staff',
            'pagila.categories',
            'pagila.actors',
            'pagila.languages',
            'pagila.cities',
            'pagila.countries',
        ];
    }
}
```

- [ ] **Step 4: Run + commit**

```bash
php artisan test --compact --filter=TransformPagila
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/Mapping/Pagila/
git commit -m "feat: implement Pagila mapper family (15 tables, address denorm, partition collapse, circular FK)"
```

### Task E3: Pagila search-projection trigger verification

- [ ] **Step 1: Write + run the trigger test** (mirror Task C3, assert `pagila.search_projections` has rows for `entity_type` 'film' and 'actor'):

```php
// tests/Feature/Import/PagilaSearchProjectionTriggerTest.php
<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Pagila\PagilaProductMapper;
use App\Services\ProductImport\PostgresSourceReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('populates pagila.search_projections via triggers when the transform inserts films and actors', function () {
    DB::statement('DROP SCHEMA IF EXISTS pagila_staging CASCADE');
    DB::statement('CREATE SCHEMA pagila_staging');
    $reader = app(PostgresSourceReader::class);
    $reader->executeSqlDump(base_path('tests/Fixtures/Sources/pagila/schema-minimal.sql'), 'pagila_staging');
    $reader->executeSqlDump(base_path('tests/Fixtures/Sources/pagila/data-minimal.sql'), 'pagila_staging');

    app(PagilaProductMapper::class)->load('pagila_staging');

    expect(DB::table('pagila.search_projections')->whereIn('entity_type', ['film', 'actor'])->count())->toBeGreaterThanOrEqual(1);
});
```

- [ ] **Step 2: Run + commit**

### Task E4: Payment partition-collapse edge case

If the `PostgresSourceReader` loads Pagila data and the 7 `payment_p2022_NN` partitions survive as separate staging tables (they won't with the current reader — it rewrites `public.payment_p2022_01` → `<staging>.payment_p2022_01`), the `PaymentMapper` must read from all of them.

- [ ] **Step 1: Decide the strategy.** Verify by loading the real Pagila data fixture and inspecting `pagila_staging` table names. If partitions are merged into one `payment` table, `PaymentMapper` is straightforward (table above). If not, override `PaymentMapper::load()` to read from `UNION ALL` of all `payment_p2022_*` tables. Document the chosen strategy in a PHPDoc on `PaymentMapper`.

---

## Phase F — Wire the transform into the importers

### Task F1: Refactor the three importers to call the product mappers

**Files:**

- Modify: `app/Services/ProductImport/ChinookImporter.php`
- Modify: `app/Services/ProductImport/NorthwindImporter.php`
- Modify: `app/Services/ProductImport/PagilaImporter.php`

**Interfaces:**

- Consumes: `ChinookProductMapper`, `NorthwindProductMapper`, `PagilaProductMapper`
- Produces: importers that load staging → transform into live domain tables → drop staging

- [ ] **Step 1: Update `ChinookImporter::import()`** (replace the Phase-A interim body):

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
    ) {}

    /**
     * Execute Chinook import into PostgreSQL schema.
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
            return ['success' => true];
        }

        $stagingSchema = 'chinook_staging';

        try {
            DB::statement('DROP SCHEMA IF EXISTS '.$stagingSchema.' CASCADE;');
            DB::statement('CREATE SCHEMA '.$stagingSchema.';');

            $this->processSourceRows($stagingSchema);

            app(ChinookProductMapper::class)->load($stagingSchema);

            DB::statement('DROP SCHEMA IF EXISTS '.$stagingSchema.' CASCADE;');

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process source rows into staging schema.
     */
    private function processSourceRows(string $stagingSchema): void
    {
        $sourceFile = $this->getSourceFilePath();

        if ($sourceFile !== null && File::exists($sourceFile)) {
            $excludePatterns = [
                '/CREATE\s+DATABASE/i',
                '/\\\\c/i',
            ];
            $this->pgReader->executeSqlDump($sourceFile, $stagingSchema, $excludePatterns);
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

- [ ] **Step 2: Apply the same shape to `NorthwindImporter`** (use `NorthwindProductMapper`)

- [ ] **Step 3: Apply the same shape to `PagilaImporter`** (use `PagilaProductMapper`; remember it has two source files — load schema then data into staging, then transform)

- [ ] **Step 4: Run the full import test suite**

```bash
php artisan test --compact --filter=Import
```

Expected: PASS — including `SchemaPreservationTest` (the original regression test from Task A2).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ProductImport/ChinookImporter.php \
        app/Services/ProductImport/NorthwindImporter.php \
        app/Services/ProductImport/PagilaImporter.php
git commit -m "feat: wire upstream→UUID transform into the three importers"
```

### Task F2: End-to-end admin-dashboard verification

- [ ] **Step 1: Fetch real source data + run a real import**

```bash
php artisan source:fetch chinook
php artisan product:import chinook
```

- [ ] **Step 2: Verify the portfolio view reflects imported data**

```bash
php artisan tinker --execute 'print_r(App\Services\Portfolio\PortfolioSnapshotStats::byProduct()["chinook"]);'
```

Expected: the `Artists`/`Albums`/`Tracks` counts are non-zero and reflect the imported rows.

- [ ] **Step 3: Verify `/admin` and `/admin/portfolio` render**

Open both URLs in a browser. Expected: no `QueryException`; stats cards show live counts; the view survived the import.

> **If `source:fetch` fails** (network/auth), fall back to running the transform tests (`php artisan test --compact --filter=Transform`) which use local fixtures — they prove the transform works. Note the fetch failure as a follow-up.

### Task F3: Full test suite + final commit

- [ ] **Step 1: Run the entire test suite**

```bash
php artisan test --compact
```

Expected: all green. Investigate and fix any failures.

- [ ] **Step 2: Pint the whole project**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 3: Commit any remaining changes**

```bash
git add -A
git commit -m "test: full suite green after import cascade fix and transform layer"
```

- [ ] **Step 4: Update the beads tracker**

```bash
bd ready     # review any beads filed for this work
bd close <id>  # close completed beads
```

---

## Self-Review

**1. Spec coverage:**

- ✅ Remove `DROP SCHEMA CASCADE` from importers + migrations (Tasks A1, A3)
- ✅ Unblock `/admin` (Task A0)
- ✅ Regression test for the view (Task A2)
- ✅ Transform infrastructure: `TableMapper`, `SelfReferentialMapper`, `ProductMapper` (Tasks B2–B4)
- ✅ Raw-DB inserts (Eloquent blocked during run) — `TableMapper::load()` uses `DB::table()->insert()`, `SourceIdentityRegistry` verified safe (Task B1)
- ✅ Chinook mapper family (Phase C)
- ✅ Northwind mapper family incl. string PKs, dropped tables, `discontinued` cast (Phase D)
- ✅ Pagila mapper family incl. address denorm, partition collapse, circular FK (Phase E)
- ✅ Wire transform into importers (Task F1)
- ✅ Search-projection triggers fire on transformed inserts (Tasks C3, D3, E3)

**2. Placeholder scan:** No "TODO"/"TBD"/"implement later". The Pagila `PaymentMapper` partition-collapse strategy is flagged as a verify-and-decide step (Task E4) because it depends on runtime staging-table shape — this is a genuine unknown, not a placeholder; the default path (single `payment` table) is fully specified.

**3. Type consistency:**

- `TableMapper::load(string $stagingSchema): int` — consistent across base, `SelfReferentialMapper`, all concrete mappers.
- `ProductMapper::load(string $stagingSchema): array{tables: int, rows: int}` — consistent.
- `SourceIdentityRegistry::getOrMint(string $entity, array $sourceKey): string` — used identically in `TableMapper` and `SelfReferentialMapper`.
- `entity()` values match the `source_identities.entity` CHECK regex `^(chinook|northwind|pagila)\.[a-z_][a-z0-9_]*$` in all mappers.
- `ProductMapper` constructor accepts `SourceIdentityRegistry $registry` (Task B4) — concrete product mappers (C2/D2/E2) resolve their TableMappers in `mappers()`, passing `$this->registry`.
