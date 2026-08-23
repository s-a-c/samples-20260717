<?php

declare(strict_types=1);

use App\Services\Portfolio\PortfolioSnapshotStats;
use App\Services\ProductImport\PortfolioViewRecreator;
use Illuminate\Support\Facades\DB;

covers(PortfolioViewRecreator::class);

test('recreates the portfolio view after it is dropped', function () {
    DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBeNull();

    app(PortfolioViewRecreator::class)->recreate();

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');
});

test('recreated view returns three product rows with correct stats shape', function () {
    DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');

    app(PortfolioViewRecreator::class)->recreate();

    $stats = PortfolioSnapshotStats::byProduct();

    expect($stats)->toHaveKeys(['chinook', 'northwind', 'pagila']);

    foreach ($stats as $productKey => $productStats) {
        expect($productStats)->not->toBeEmpty();

        foreach ($productStats as $stat) {
            expect($stat)
                ->toHaveKey('label')
                ->toHaveKey('value')
                ->and($stat['value'])->toBeString();
        }
    }
});

test('view is not silently present after a schema drop cascade that removes dependents', function () {
    // Drop the chinook schema cascade — this drops the dependent view too
    DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE');

    // Recreate chinook so the view can reference it
    DB::statement('CREATE SCHEMA chinook');
    // Need minimal tables for the view references
    DB::statement('CREATE TABLE chinook.artists (id uuid, name text)');
    DB::statement('CREATE TABLE chinook.tracks (id uuid, name text)');

    // The view should have been dropped by the cascade
    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBeNull();

    // Recreate it
    app(PortfolioViewRecreator::class)->recreate();

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');
});

test('recreate is idempotent — calling it twice does not error', function () {
    $recreator = app(PortfolioViewRecreator::class);

    $recreator->recreate();
    $recreator->recreate();

    expect(DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation")->relation)
        ->toBe('product_portfolio_snapshots');
});
