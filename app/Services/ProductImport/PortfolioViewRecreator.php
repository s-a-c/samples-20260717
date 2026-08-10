<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;

/**
 * Recreate the {@see product_portfolio_snapshots} public view.
 *
 * PostgreSQL's DROP SCHEMA ... CASCADE recursively removes dependent
 * objects across all schemas. The portfolio view lives in public but
 * references tables in chinook, northwind, and pagila, so every
 * shadow-schema publish that drops a product schema silently drops
 * this view.
 *
 * Laravel's migration ledger records the view migration as "ran", so
 * php artisan migrate will never repair it. This service provides the
 * single source of truth for the view DDL and is called after every
 * successful schema publish.
 */
class PortfolioViewRecreator
{
    /**
     * Recreate the portfolio view.
     *
     * Safe to call when the view already exists (CREATE OR REPLACE).
     */
    public function recreate(): void
    {
        DB::statement($this->createViewSql());
    }

    /**
     * The exact DDL shared with the original migration.
     *
     * Returns the raw SQL so the migration and this service can never drift.
     */
    public function createViewSql(): string
    {
        return <<<'SQL'
            CREATE OR REPLACE VIEW public.product_portfolio_snapshots AS
            SELECT
                'chinook'::text AS product,
                jsonb_build_array(
                    jsonb_build_object('label', 'Tables',  'value', (SELECT count(*)::text FROM information_schema.tables WHERE table_schema = 'chinook'  AND table_type = 'BASE TABLE')),
                    jsonb_build_object('label', 'Artists', 'value', (SELECT count(*)::text FROM chinook.artists)),
                    jsonb_build_object('label', 'Tracks',  'value', (SELECT count(*)::text FROM chinook.tracks))
                ) AS stats
            UNION ALL
            SELECT
                'northwind'::text AS product,
                jsonb_build_array(
                    jsonb_build_object('label', 'Tables',   'value', (SELECT count(*)::text FROM information_schema.tables WHERE table_schema = 'northwind' AND table_type = 'BASE TABLE')),
                    jsonb_build_object('label', 'Products', 'value', (SELECT count(*)::text FROM northwind.products)),
                    jsonb_build_object('label', 'Orders',   'value', (SELECT count(*)::text FROM northwind.orders))
                ) AS stats
            UNION ALL
            SELECT
                'pagila'::text AS product,
                jsonb_build_array(
                    jsonb_build_object('label', 'Tables', 'value', (SELECT count(*)::text FROM information_schema.tables WHERE table_schema = 'pagila' AND table_type = 'BASE TABLE')),
                    jsonb_build_object('label', 'Films',  'value', (SELECT count(*)::text FROM pagila.films)),
                    jsonb_build_object('label', 'Actors', 'value', (SELECT count(*)::text FROM pagila.actors))
                ) AS stats
            SQL;
    }
}
