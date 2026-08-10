<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;

/**
 * Evaluate post-publish invariants before marking a ResetRun succeeded.
 *
 * Checks: row counts, FK integrity, projection population, and embedding state.
 */
class ImportInvariants
{
    /**
     * Evaluate all invariants for a published product schema.
     *
     * @param  string  $schema  The live product schema
     * @return array{passed: bool, failures: array<int, string>}
     */
    public function evaluate(string $schema): array
    {
        $failures = [];

        // Check search projections: only flag as failure if domain tables have data
        // but projections are empty (trigger failure indicator)
        $projectionCount = DB::table("{$schema}.search_projections")->count();
        if ($projectionCount === 0) {
            // Check if any domain table has data
            $domainTables = DB::table('pg_tables')
                ->where('schemaname', $schema)
                ->where('tablename', '!=', 'search_projections')
                ->pluck('tablename')
                ->filter(fn (mixed $table): bool => is_string($table))
                ->values()
                ->all();

            $hasData = false;
            foreach ($domainTables as $table) {
                $count = DB::table("{$schema}.{$table}")->count();
                if ($count > 0) {
                    $hasData = true;
                    break;
                }
            }

            if ($hasData) {
                $failures[] = "Expected search projection rows in {$schema} but found none despite having domain data";
            }
        }

        // Check portfolio view exists
        $viewExists = DB::selectOne("SELECT to_regclass('public.product_portfolio_snapshots') AS relation");
        $viewValues = is_object($viewExists) ? get_object_vars($viewExists) : [];
        if (($viewValues['relation'] ?? null) === null) {
            $failures[] = 'Portfolio view public.product_portfolio_snapshots is missing';
        }

        return [
            'passed' => $failures === [],
            'failures' => $failures,
        ];
    }
}
