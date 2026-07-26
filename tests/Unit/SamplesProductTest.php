<?php

declare(strict_types=1);

use App\Enums\SamplesProduct;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

covers(SamplesProduct::class);

test('enum is string-backed with the three sample products', function () {
    expect(SamplesProduct::cases())
        ->toHaveCount(3)
        ->and(SamplesProduct::Chinook->value)->toBe('chinook')
        ->and(SamplesProduct::Northwind->value)->toBe('northwind')
        ->and(SamplesProduct::Pagila->value)->toBe('pagila');
});

test('it implements the Filament label, color, and icon contracts', function () {
    expect(SamplesProduct::Chinook)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);
});

test('each case exposes its Filament identity via profile delegation', function (SamplesProduct $product, string $label, string $color, Heroicon $icon) {
    expect($product->getLabel())->toBe($label)
        ->and($product->getColor())->toBe($color)
        ->and($product->getIcon())->toBe($icon)
        ->and($product->icon())->toBe($icon);
})->with([
    'chinook' => [SamplesProduct::Chinook, 'Chinook', 'violet', Heroicon::OutlinedMusicalNote],
    'northwind' => [SamplesProduct::Northwind, 'Northwind', 'sky', Heroicon::OutlinedTruck],
    'pagila' => [SamplesProduct::Pagila, 'Pagila', 'rose', Heroicon::OutlinedFilm],
]);

test('filament color returns the full palette array matching the panel primary', function (SamplesProduct $product, array $expected) {
    expect($product->filamentColor())->toBe($expected);
})->with([
    'chinook' => [SamplesProduct::Chinook, Color::Violet],
    'northwind' => [SamplesProduct::Northwind, Color::Sky],
    'pagila' => [SamplesProduct::Pagila, Color::Rose],
]);

test('curator role follows the product curator convention', function (SamplesProduct $product, string $role) {
    expect($product->curatorRole())->toBe($role);
})->with([
    'chinook' => [SamplesProduct::Chinook, 'chinook_curator'],
    'northwind' => [SamplesProduct::Northwind, 'northwind_curator'],
    'pagila' => [SamplesProduct::Pagila, 'pagila_curator'],
]);

test('panel id and path are the slug, and url prefixes a slash', function () {
    expect(SamplesProduct::Chinook->panelId())->toBe('chinook')
        ->and(SamplesProduct::Chinook->panelPath())->toBe('chinook')
        ->and(SamplesProduct::Chinook->url())->toBe('/chinook');
});

test('from panel id resolves sample products and rejects non-products', function () {
    expect(SamplesProduct::fromPanelId('chinook'))->toBe(SamplesProduct::Chinook)
        ->and(SamplesProduct::fromPanelId('northwind'))->toBe(SamplesProduct::Northwind)
        ->and(SamplesProduct::fromPanelId('pagila'))->toBe(SamplesProduct::Pagila)
        ->and(SamplesProduct::fromPanelId('admin'))->toBeNull()
        ->and(SamplesProduct::fromPanelId('unknown'))->toBeNull();
});

test('description is stable product identity, not volatile stats', function () {
    expect(SamplesProduct::Chinook->description())
        ->toContain('Digital media store')
        ->and(SamplesProduct::Northwind->description())->toContain('order-management')
        ->and(SamplesProduct::Pagila->description())->toContain('DVD rental');
});
