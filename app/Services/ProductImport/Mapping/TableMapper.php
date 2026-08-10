<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Abstract per-table mapper: reads source rows and writes staging rows.
 *
 * Concrete subclasses define the entity name, source table, staging model
 * class, source key columns, column mappings, and FK mappings. The base
 * provides the load() algorithm: read source → map rows → resolve FKs →
 * save through Eloquent staging subclasses with observer suppression.
 */
abstract class TableMapper
{
    /**
     * Load source rows into staging via Eloquent staging models.
     *
     * @param  string  $sourceSchema  The upstream-shaped source schema
     * @param  string  $stagingSchema  The app-shaped staging schema
     * @return int Number of rows loaded
     */
    abstract public function load(string $sourceSchema, string $stagingSchema): int;

    protected function sourceInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Expected an integer source value.');
    }

    protected function sourceFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if ((is_int($value) || is_string($value)) && is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException('Expected a numeric source value.');
    }

    /**
     * Count source rows for progress reporting.
     */
    protected function countSourceRows(string $sourceSchema, string $table): int
    {
        return (int) DB::table("{$sourceSchema}.{$table}")->count();
    }

    /**
     * Read source rows from the source schema.
     *
     * @return array<int, object>
     */
    protected function readSourceRows(string $sourceSchema, string $table): array
    {
        return DB::table("{$sourceSchema}.{$table}")->get()->all();
    }
}
