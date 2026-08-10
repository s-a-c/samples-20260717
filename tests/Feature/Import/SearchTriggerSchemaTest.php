<?php

declare(strict_types=1);

use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Support\Facades\DB;

covers(App\Services\ProductImport\Schema\SearchProjectionSchema::class);

function searchDatabaseInteger(mixed $value): int
{
    $integer = filter_var($value, FILTER_VALIDATE_INT);

    if ($integer === false) {
        throw new RuntimeException('Expected an integer database value.');
    }

    return $integer;
}

afterEach(function () {
    foreach (['chinook', 'northwind', 'pagila'] as $product) {
        DB::statement("DROP SCHEMA IF EXISTS {$product}_staging CASCADE");
    }
});

test('staging trigger writes to staging search_projections not live', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');

    // Insert a row into staging artists table
    DB::statement(<<<'SQL'
        INSERT INTO chinook_staging.artists (id, name, created_at, updated_at)
        VALUES ('019fe900-0000-7000-8000-000000000001', 'Test Artist', NOW(), NOW())
    SQL);

    // Check staging search_projections has the row
    $stagingCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook_staging.search_projections') ?? (object) ['cnt' => 0];
    expect(searchDatabaseInteger($stagingCount->cnt))->toBe(1);

    // Verify it is a 'artist' entity type
    $projection = DB::selectOne("SELECT entity_type, weight_d_text FROM chinook_staging.search_projections WHERE id = '019fe900-0000-7000-8000-000000000001'");
    expect($projection->entity_type)->toBe('artist')
        ->and($projection->weight_d_text)->toBe('Test Artist');
});

test('staging trigger does NOT write to live search_projections', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');

    // Clear live search_projections to be sure
    DB::statement('DELETE FROM chinook.search_projections');

    // Insert into staging
    DB::statement(<<<'SQL'
        INSERT INTO chinook_staging.artists (id, name, created_at, updated_at)
        VALUES ('019fe900-0000-7000-8000-000000000002', 'Staging Only Artist', NOW(), NOW())
    SQL);

    // Live search_projections should be empty
    $liveCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook.search_projections') ?? (object) ['cnt' => 0];
    expect(searchDatabaseInteger($liveCount->cnt))->toBe(0);
});

test('pagila stores trigger works without address column', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('pagila');

    // Insert a store row (no address column)
    DB::statement(<<<'SQL'
        INSERT INTO pagila_staging.stores (id, manager_staff_id, address_id, created_at, updated_at)
        VALUES ('019fe900-0000-7000-8000-000000000010', NULL, NULL, NOW(), NOW())
    SQL);

    $projection = DB::selectOne("SELECT entity_type, weight_d_text FROM pagila_staging.search_projections WHERE id = '019fe900-0000-7000-8000-000000000010'");
    expect($projection)->not->toBeNull()
        ->and($projection->entity_type)->toBe('store')
        ->and($projection->weight_d_text)->toContain('Store');
});

test('staging delete trigger removes projection row', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');

    DB::statement(<<<'SQL'
        INSERT INTO chinook_staging.genres (id, name, created_at, updated_at)
        VALUES ('019fe900-0000-7000-8000-000000000003', 'Test Genre', NOW(), NOW())
    SQL);

    $beforeCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook_staging.search_projections');
    expect(searchDatabaseInteger($beforeCount->cnt))->toBe(1);

    DB::statement("DELETE FROM chinook_staging.genres WHERE id = '019fe900-0000-7000-8000-000000000003'");

    $afterCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook_staging.search_projections');
    expect(searchDatabaseInteger($afterCount->cnt))->toBe(0);
});
