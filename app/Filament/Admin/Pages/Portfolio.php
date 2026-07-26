<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\SamplesProduct;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

final class Portfolio extends Page
{
    /**
     * Volatile presentation figures per Sample Product, keyed by panel id.
     *
     * Product identity (label, description, icon, url) lives on
     * {@see SamplesProduct}; only these counts — which drift from real data
     * over time — are kept here as presentation data.
     *
     * @var array<string, array<int, array{label: string, value: string}>>
     */
    private const STATS = [
        'chinook' => [
            ['label' => 'Tables', 'value' => '12'],
            ['label' => 'Artists', 'value' => '275+'],
            ['label' => 'Tracks', 'value' => '3,500+'],
        ],
        'northwind' => [
            ['label' => 'Tables', 'value' => '13'],
            ['label' => 'Products', 'value' => '75+'],
            ['label' => 'Orders', 'value' => '830+'],
        ],
        'pagila' => [
            ['label' => 'Tables', 'value' => '16'],
            ['label' => 'Films', 'value' => '1,000'],
            ['label' => 'Actors', 'value' => '200+'],
        ],
    ];

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected string $view = 'filament.admin.pages.portfolio';

    protected static ?string $slug = 'portfolio';

    /** @return array{products: array<int, array{key: string, name: string, description: string, url: string, icon: Heroicon, stats: array<int, array{label: string, value: string}>}>} */
    protected function getViewData(): array
    {
        $products = array_map(
            fn (SamplesProduct $product) => [
                'key' => $product->value,
                'name' => $product->getLabel(),
                'description' => $product->description(),
                'url' => $product->url(),
                'icon' => $product->icon(),
                'stats' => self::STATS[$product->value],
            ],
            SamplesProduct::cases(),
        );

        return ['products' => $products];
    }
}
