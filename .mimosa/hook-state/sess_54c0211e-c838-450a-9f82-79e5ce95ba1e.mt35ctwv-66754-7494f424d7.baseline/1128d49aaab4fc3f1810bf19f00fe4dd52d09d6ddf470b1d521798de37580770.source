<?php

declare(strict_types=1);

use App\Services\ProductImport\Schema\SearchProjectionSchema;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

covers(StagingSchemaBuilder::class, SearchProjectionSchema::class);

function assertStagingSchemaExists(string $product): void
{
    $stagingSchema = "{$product}_staging";
    $liveSchema = $product;

    // Schema exists
    $exists = DB::selectOne(
        'SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = ?) AS exists',
        [$stagingSchema]
    );
    expect($exists->exists)->toBeTrue("Staging schema {$stagingSchema} should exist");

    // Has search_projections table
    $spExists = Schema::hasTable("{$stagingSchema}.search_projections");
    expect($spExists)->toBeTrue("{$stagingSchema}.search_projections should exist");

    // Has the same domain tables as the live schema (minus search_projections)
    $liveTables = collect(DB::select("
        SELECT tablename FROM pg_tables WHERE schemaname = '{$liveSchema}'
        ORDER BY tablename
    "))->pluck('tablename')
        ->filter(fn (mixed $table): bool => is_string($table) && $table !== 'search_projections')
        ->values();

    foreach ($liveTables as $table) {
        $exists = Schema::hasTable("{$stagingSchema}.{$table}");
        expect($exists)->toBeTrue("{$stagingSchema}.{$table} should exist");
    }
}

afterEach(function () {
    foreach (['chinook', 'northwind', 'pagila'] as $product) {
        DB::statement("DROP SCHEMA IF EXISTS {$product}_staging CASCADE");
    }
});

test('build creates chinook_staging with app-shaped tables and search projections', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');

    assertStagingSchemaExists('chinook');
});

test('build creates northwind_staging with app-shaped tables and search projections', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('northwind');

    assertStagingSchemaExists('northwind');
});

test('build creates pagila_staging with app-shaped tables and search projections', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('pagila');

    assertStagingSchemaExists('pagila');
});

test('build throws for unknown product', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('unknown');
})->throws(InvalidArgumentException::class, 'Unknown product: unknown');

test('staging tables have UUID primary keys', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');

    // Check that artists table has uuid PK
    $columns = DB::select("
        SELECT data_type FROM information_schema.columns
        WHERE table_schema = 'chinook_staging' AND table_name = 'artists' AND column_name = 'id'
    ");
    expect($columns[0]->data_type)->toBe('uuid');
});

test('drop removes the staging schema', function () {
    $builder = app(StagingSchemaBuilder::class);

    $builder->build('chinook');
    $builder->drop('chinook');

    $exists = DB::selectOne("SELECT to_regclass('chinook_staging.artists') AS relation");
    expect($exists->relation)->toBeNull();
});

test('dropSource removes the isolated source schema', function () {
    DB::statement('CREATE SCHEMA IF NOT EXISTS chinook_source');

    app(StagingSchemaBuilder::class)->dropSource('chinook');

    $exists = DB::selectOne("SELECT to_regnamespace('chinook_source') AS relation");
    expect($exists->relation)->toBeNull();
});

test('search projection schema can drop its projection table', function () {
    app(StagingSchemaBuilder::class)->build('chinook');

    SearchProjectionSchema::drop('chinook_staging');

    expect(Schema::hasTable('chinook_staging.search_projections'))->toBeFalse();
});
