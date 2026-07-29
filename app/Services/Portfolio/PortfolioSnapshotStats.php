<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use Illuminate\Support\Facades\DB;

/**
 * Read cross-product portfolio statistics from the
 * {@see product_portfolio_snapshots} Postgres view.
 *
 * Lives in the service layer (not the Filament presentation layer) so that
 * presentation classes stay free of direct DB facade usage per ADR 100326
 * (Wayfinder #17 arch rule: "Filament layer must not use DB facade").
 */
final class PortfolioSnapshotStats
{
    /**
     * Per-product stat rows decoded from the snapshot view's JSONB column.
     *
     * Each stat is validated to its {label, value} shape so callers get a
     * typed array even though the view's JSONB column decodes to mixed.
     *
     * @return array<string, array<int, array{label: string, value: string}>>
     */
    public static function byProduct(): array
    {
        /** @var array<string, array<int, array{label: string, value: string}>> $byProduct */
        $byProduct = [];

        foreach (DB::table('product_portfolio_snapshots')->get() as $row) {
            $product = $row->product;
            $raw = $row->stats;
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            $stats = [];

            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_array($item) && isset($item['label'], $item['value'])
                        && is_string($item['label']) && is_string($item['value'])) {
                        $stats[] = ['label' => $item['label'], 'value' => $item['value']];
                    }
                }
            }

            if (is_string($product)) {
                $byProduct[$product] = $stats;
            }
        }

        return $byProduct;
    }
}
