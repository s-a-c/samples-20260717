<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\SamplesProduct;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

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

        return [
            'product' => $this->productKey !== null && isset($products[$this->productKey])
                ? $products[$this->productKey]
                : null,
        ];
    }

    /**
     * Live per-product stats read from the product_portfolio_snapshots view.
     *
     * Each stat is validated to its {label, value} shape so callers get a
     * typed array even though the view's JSONB column decodes to mixed.
     *
     * @return array<string, array<int, array{label: string, value: string}>>
     */
    private static function snapshotStats(): array
    {
        /** @var array<string, array<int, array{label: string, value: string}>> $byProduct */
        $byProduct = [];

        foreach (DB::table('product_portfolio_snapshots')->get() as $row) {
            $raw = $row->stats;
            $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            $stats = [];

            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_array($item) && isset($item['label'], $item['value'])
                        && is_string($item['label']) && is_string($item['value'])) {
                        $stats[] = ['label' => $item['label'], 'value' => $item['value']];
                    }
                }
            }

            $byProduct[(string) $row->product] = $stats;
        }

        return $byProduct;
    }
}
