<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create the public.product_portfolio_snapshots Postgres view.
 *
 * Per CONTEXT.md, a Product Portfolio Snapshot is the derived status of one
 * Sample Product used by the Product Portfolio — generic operational facts,
 * not an analytical business entity. Each row is one Sample Product with a
 * labelled stats array consumed by the ProductPortfolioCard widget.
 *
 * The view must run AFTER the chinook/northwind/pagila schema migrations
 * (212xxx) because Postgres validates view column references at CREATE time.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement($this->createViewSql());
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');
    }

    private function createViewSql(): string
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
};
