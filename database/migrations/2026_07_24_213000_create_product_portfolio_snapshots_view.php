<?php

declare(strict_types=1);

use App\Services\ProductImport\PortfolioViewRecreator;
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
 *
 * The DDL is shared with {@see PortfolioViewRecreator} so that fresh
 * installations and post-swap repairs use the same SQL. The recreator is
 * called after every shadow-schema publish because DROP SCHEMA ... CASCADE
 * silently drops this dependent public view.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(app(PortfolioViewRecreator::class)->createViewSql());
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');
    }
};
