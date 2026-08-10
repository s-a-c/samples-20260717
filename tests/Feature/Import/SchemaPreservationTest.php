<?php

declare(strict_types=1);

use App\Services\ProductImport\PortfolioViewRecreator;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Support\Facades\DB;

covers(PortfolioViewRecreator::class, StagingSchemaBuilder::class);

function schemaDatabaseInteger(mixed $value): int
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

test('portfolio view survives staging build and swap', function () {
    $builder = app(StagingSchemaBuilder::class);

    // Build staging
    $builder->build('chinook');

    // View should still exist (staging build doesn't drop live schema)
    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');

    // Simulate publish: drop live, rename staging
    DB::transaction(function () {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE');
        DB::statement('ALTER SCHEMA chinook_staging RENAME TO chinook');
        app(PortfolioViewRecreator::class)->recreate();
    });

    // View should exist after swap + recreate
    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');

    // And still return three products
    $rows = DB::select('SELECT product FROM product_portfolio_snapshots ORDER BY product');
    expect(collect($rows)->pluck('product')->all())->toBe(['chinook', 'northwind', 'pagila']);
});

test('staging build does not affect live schema data', function () {
    // Insert data into live chinook
    DB::statement(<<<'SQL'
        INSERT INTO chinook.artists (id, name, created_at, updated_at)
        VALUES ('019fe900-1000-7000-8000-000000000001', 'Live Artist', NOW(), NOW())
    SQL);

    $liveCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook.artists');
    expect(schemaDatabaseInteger($liveCount->cnt))->toBe(1);

    $builder = app(StagingSchemaBuilder::class);
    $builder->build('chinook');

    // Live data should be unaffected
    $liveCountAfter = DB::selectOne('SELECT count(*) AS cnt FROM chinook.artists');
    expect(schemaDatabaseInteger($liveCountAfter->cnt))->toBe(1);

    // Staging should be empty (freshly built)
    $stagingCount = DB::selectOne('SELECT count(*) AS cnt FROM chinook_staging.artists');
    expect(schemaDatabaseInteger($stagingCount->cnt))->toBe(0);
});
