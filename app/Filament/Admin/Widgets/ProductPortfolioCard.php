<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class ProductPortfolioCard extends Widget
{
    protected string $view = 'filament.admin.widgets.product-portfolio-card';

    /** @var string|null Product key to display (chinook, northwind, pagila) */
    public ?string $productKey = null;

    protected static ?int $sort = 1;

    /**
     * @return array<string, array{key: string, name: string, description: string, url: string, icon: string, stats: array<int, array{label: string, value: string}>}>
     */
    public static function getProducts(): array
    {
        return [
            'chinook' => [
                'key' => 'chinook',
                'name' => 'Chinook',
                'description' => 'Digital media store sample dataset featuring artists, albums, tracks, and customers — showcasing a music sales platform.',
                'url' => '/chinook',
                'icon' => 'heroicon-o-musical-note',
                'stats' => [
                    ['label' => 'Tables', 'value' => '12'],
                    ['label' => 'Artists', 'value' => '275+'],
                    ['label' => 'Tracks', 'value' => '3,500+'],
                ],
            ],
            'northwind' => [
                'key' => 'northwind',
                'name' => 'Northwind',
                'description' => 'Classic order-management sample dataset with products, suppliers, customers, and orders — demonstrating a trading enterprise.',
                'url' => '/northwind',
                'icon' => 'heroicon-o-truck',
                'stats' => [
                    ['label' => 'Tables', 'value' => '13'],
                    ['label' => 'Products', 'value' => '75+'],
                    ['label' => 'Orders', 'value' => '830+'],
                ],
            ],
            'pagila' => [
                'key' => 'pagila',
                'name' => 'Pagila',
                'description' => 'DVD rental store sample dataset featuring films, actors, customers, and rentals — illustrating a rental business domain.',
                'url' => '/pagila',
                'icon' => 'heroicon-o-film',
                'stats' => [
                    ['label' => 'Tables', 'value' => '16'],
                    ['label' => 'Films', 'value' => '1,000'],
                    ['label' => 'Actors', 'value' => '200+'],
                ],
            ],
        ];
    }

    /** @return array{product: array|null} */
    protected function getViewData(): array
    {
        $products = self::getProducts();

        return [
            'product' => $this->productKey !== null && isset($products[$this->productKey])
                ? $products[$this->productKey]
                : null,
        ];
    }
}
