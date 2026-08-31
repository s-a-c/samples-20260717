<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

/**
 * Mapper for tables with self-referential foreign keys.
 *
 * Uses a two-pass approach: insert with self-FK null, collect the
 * source→UUID map, then UPDATE to set the resolved value.
 */
abstract class SelfReferentialMapper extends TableMapper
{
    /**
     * Load source rows with two-pass self-referential FK resolution.
     *
     * @param  string  $sourceSchema  The upstream-shaped source schema
     * @param  string  $stagingSchema  The app-shaped staging schema
     * @return int Number of rows loaded
     */
    #[\Override]
    abstract public function load(string $sourceSchema, string $stagingSchema): int;
}
