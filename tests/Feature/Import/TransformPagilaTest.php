<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Pagila\PagilaProductMapper;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

covers(
    PagilaProductMapper::class,
    App\Services\ProductImport\Mapping\Pagila\CountryCityMapper::class,
    App\Services\ProductImport\Mapping\Pagila\CategoryMapper::class,
    App\Services\ProductImport\Mapping\Pagila\LanguageMapper::class,
    App\Services\ProductImport\Mapping\Pagila\ActorMapper::class,
    App\Services\ProductImport\Mapping\Pagila\FilmMapper::class,
    App\Services\ProductImport\Mapping\Pagila\StoreStaffMapper::class,
    App\Services\ProductImport\Mapping\Pagila\CustomerMapper::class,
    SourceSchemaBuilder::class,
);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS pagila_source CASCADE');
    DB::statement('DROP SCHEMA IF EXISTS pagila_staging CASCADE');
});

beforeEach(function () {
    SourceSchemaBuilder::buildPagila();
    $sql = File::get(base_path('tests/Fixtures/Sources/pagila/minimal.sql'));
    $lines = explode("\n", $sql);
    $codeLines = array_filter($lines, fn (string $line): bool => ! str_starts_with(mb_trim($line), '--'));
    $cleanSql = implode("\n", $codeLines);
    foreach (array_filter(
        array_map('trim', explode(';', $cleanSql)),
        fn (string $statement): bool => $statement !== '',
    ) as $statement) {
        if ($statement !== '') {
            DB::statement($statement);
        }
    }
    app(StagingSchemaBuilder::class)->build('pagila');
});

test('pagila transform loads films with UUID PKs', function () {
    $mapper = new PagilaProductMapper;
    $mapper->load('pagila_source', 'pagila_staging');

    $films = DB::table('pagila_staging.films')->get();
    expect($films)->toHaveCount(2);

    foreach ($films as $film) {
        expect($film->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    }
});

test('pagila transform resolves film language FK', function () {
    $mapper = new PagilaProductMapper;
    $mapper->load('pagila_source', 'pagila_staging');

    $films = DB::table('pagila_staging.films')->get();
    $languageIds = DB::table('pagila_staging.languages')->pluck('id')->all();

    foreach ($films as $film) {
        expect($languageIds)->toContain($film->language_id);
    }
});

test('pagila transform resolves circular store-staff FK', function () {
    $mapper = new PagilaProductMapper;
    $mapper->load('pagila_source', 'pagila_staging');

    $stores = DB::table('pagila_staging.stores')->get();
    $staff = DB::table('pagila_staging.staff')->get();

    expect($stores)->toHaveCount(1);
    expect($staff)->toHaveCount(1);

    $store = $stores->first();
    $staffMember = $staff->first();

    // Circular FK: store.manager_staff_id = staff.id, staff.store_id = store.id
    expect($store?->manager_staff_id)->toBe($staffMember?->id)->and($staffMember?->store_id)->toBe($store?->id);
});

test('pagila transform loads customers with store FK', function () {
    $mapper = new PagilaProductMapper;
    $mapper->load('pagila_source', 'pagila_staging');

    $customers = DB::table('pagila_staging.customers')->get();
    expect($customers)->toHaveCount(2);

    $storeIds = DB::table('pagila_staging.stores')->pluck('id')->all();
    foreach ($customers as $customer) {
        expect($storeIds)->toContain($customer->store_id);
    }
});

test('pagila transform creates search projections', function () {
    $mapper = new PagilaProductMapper;
    $mapper->load('pagila_source', 'pagila_staging');

    $filmProjections = DB::table('pagila_staging.search_projections')
        ->where('entity_type', 'film')
        ->get();
    expect($filmProjections)->toHaveCount(2);
});

test('pagila mapper exposes its ordered table mappers', function () {
    $method = new ReflectionMethod(PagilaProductMapper::class, 'mappers');
    $mappers = $method->invoke(new PagilaProductMapper);

    expect($mappers)->toHaveCount(7);
});
