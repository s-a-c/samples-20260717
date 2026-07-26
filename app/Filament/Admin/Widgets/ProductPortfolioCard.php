<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\SamplesProduct;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

final class ProductPortfolioCard extends Widget
{
    /**
     * Volatile presentation figures per Sample Product, keyed by panel id.
     *
     * Product identity lives on {@see SamplesProduct}; only these counts are
     * kept here as presentation data.
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

    /** @var string|null Sample Product panel id to display (chinook, northwind, pagila). */
    public ?string $productKey = null;

    protected string $view = 'filament.admin.widgets.product-portfolio-card';

    protected static ?int $sort = 1;

    /**
     * @return array<string, array{key: string, name: string, description: string, url: string, icon: Heroicon, stats: array<int, array{label: string, value: string}>}>
     */
    public static function getProducts(): array
    {
        $products = [];

        foreach (SamplesProduct::cases() as $product) {
            $products[$product->value] = [
                'key' => $product->value,
                'name' => $product->getLabel(),
                'description' => $product->description(),
                'url' => $product->url(),
                'icon' => $product->icon(),
                'stats' => self::STATS[$product->value],
            ];
        }

        return $products;
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
