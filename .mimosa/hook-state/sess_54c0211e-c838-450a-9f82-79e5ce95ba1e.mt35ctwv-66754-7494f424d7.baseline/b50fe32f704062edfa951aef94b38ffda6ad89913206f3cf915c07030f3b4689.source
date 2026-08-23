<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ProductPortfolioCard;
use App\Models\Chinook\Artist;
use Illuminate\Support\Facades\DB;

covers(ProductPortfolioCard::class);

test('product portfolio snapshots view returns one labelled row per sample product', function () {
    $products = DB::table('product_portfolio_snapshots')->orderBy('product')->pluck('product')->all();

    expect($products)->toBe(['chinook', 'northwind', 'pagila']);
});

test('product portfolio card widget reads live stats from the view', function () {
    $products = ProductPortfolioCard::getProducts();

    expect($products)->toHaveCount(3)
        ->and($products)->toHaveKeys(['chinook', 'northwind', 'pagila']);

    // The Tables stat is derived from information_schema, so it is non-zero
    // even when the sample data tables are empty.
    foreach (['chinook', 'northwind', 'pagila'] as $productKey) {
        $tables = collect($products[$productKey]['stats'])
            ->firstWhere('label', 'Tables')['value'] ?? null;

        expect($tables)->not->toBeNull()
            ->and((int) $tables)->toBeGreaterThan(0);
    }
});

test('snapshot counts reflect inserted sample data', function () {
    Artist::create(['name' => 'Test Artist']);

    $products = ProductPortfolioCard::getProducts();

    $artists = collect($products['chinook']['stats'])
        ->firstWhere('label', 'Artists')['value'] ?? null;

    expect($artists)->toBe('1');
});
