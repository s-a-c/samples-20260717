<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

/**
 * Orchestrates per-product table mappers in dependency order.
 *
 * Concrete subclasses declare the ordered list of TableMappers and
 * the truncate order. The load() method truncates staging tables,
 * then runs each mapper in a transaction.
 */
abstract class ProductMapper
{
    /**
     * Load all tables for a product from source to staging.
     *
     * @param  string  $sourceSchema  The upstream-shaped source schema
     * @param  string  $stagingSchema  The app-shaped staging schema
     * @return array{tables: int, rows: int}
     */
    abstract public function load(string $sourceSchema, string $stagingSchema): array;

    /**
     * Get the ordered list of table mappers for this product.
     *
     * @return array<int, TableMapper>
     */
    abstract protected function mappers(): array;
}
