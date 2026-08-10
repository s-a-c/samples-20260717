<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ProductPortfolioCard;

covers(ProductPortfolioCard::class);

/**
 * @return array{product: array{key: string, name: string, description: string, url: string, icon: Filament\Support\Icons\Heroicon, stats: array<int, array{label: string, value: string}>}|null}
 */
function callViewData(?string $productKey): array
{
    $card = new ProductPortfolioCard;
    $card->productKey = $productKey;

    $method = new ReflectionMethod($card, 'getViewData');
    /**
     * @var array{product: array{key: string, name: string, description: string, url: string, icon: Filament\Support\Icons\Heroicon, stats: array<int, array{label: string, value: string}>}|null} $data
     */
    $data = $method->invoke($card);

    return $data;
}

test('product portfolio card returns the matching product when a valid key is set', function () {
    $data = callViewData('chinook');

    assert(is_array($data['product']));

    expect($data['product']['key'])->toBe('chinook')
        ->and($data['product']['name'])->toBeString();
});

test('product portfolio card returns null product when no key is set', function () {
    $data = callViewData(null);

    expect($data['product'])->toBeNull();
});

test('product portfolio card returns null product for an unknown key', function () {
    $data = callViewData('nonexistent');

    expect($data['product'])->toBeNull();
});

test('product portfolio card getProducts returns all sample products', function () {
    $products = ProductPortfolioCard::getProducts();

    expect($products)->toHaveKeys(['chinook', 'northwind', 'pagila'])
        ->and($products['chinook']['url'])->toBeString()
        ->and($products['northwind']['icon'])->not->toBeNull();
});
