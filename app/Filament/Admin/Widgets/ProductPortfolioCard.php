<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\SamplesProduct;
use App\Services\Portfolio\PortfolioSnapshotStats;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

final class ProductPortfolioCard extends Widget
{
    /** @var string|null Sample Product panel id to display (chinook, northwind, pagila). */
    public ?string $productKey = null;

    protected string $view = 'filament.admin.widgets.product-portfolio-card';

    protected static ?int $sort = 1;

    /**
     * @return array<string, array{key: string, name: string, description: string, url: string, icon: Heroicon, stats: array<int, array{label: string, value: string}>}>
     */
    public static function getProducts(): array
    {
        $stats = self::snapshotStats();
        $products = [];

        foreach (SamplesProduct::cases() as $product) {
            $products[$product->value] = [
                'key' => $product->value,
                'name' => $product->getLabel() ?? $product->value,
                'description' => $product->description(),
                'url' => $product->url(),
                'icon' => $product->icon(),
                'stats' => $stats[$product->value] ?? [],
            ];
        }

        return $products;
    }

    /** @return array{product: array|null} */
    protected function getViewData(): array
    {
        $products = self::getProducts();

        $product = $this->productKey !== null && isset($products[$this->productKey])
            ? $products[$this->productKey]
            : null;

        return ['product' => $product];
    }

    /**
     * Live per-product stats read from the product_portfolio_snapshots view,
     * via the {@see PortfolioSnapshotStats} service (keeps DB access out of
     * the Filament presentation layer — ADR 100326).
     *
     * @return array<string, array<int, array{label: string, value: string}>>
     */
    private static function snapshotStats(): array
    {
        return PortfolioSnapshotStats::byProduct();
    }
}
