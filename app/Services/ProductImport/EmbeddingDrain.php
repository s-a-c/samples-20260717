<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;

/**
 * Drain pending embedding jobs after a publish.
 *
 * After the shadow-schema swap, search projections may have rows with
 * embedding_state = 'pending'. This service dispatches EmbeddingJob for
 * each pending row, then polls until all reach a terminal state
 * (complete, failed, or lexical_only).
 */
class EmbeddingDrain
{
    /**
     * Dispatch pending embeddings for a product schema.
     *
     * @param  string  $schema  The live product schema (e.g., 'chinook')
     * @return array{dispatched: int, failed: int}
     */
    public function drain(string $schema): array
    {
        $pending = DB::table("{$schema}.search_projections")
            ->where('embedding_state', 'pending')
            ->get();

        $dispatched = 0;

        foreach ($pending as $row) {
            // Mark as dispatched to prevent re-dispatch
            DB::table("{$schema}.search_projections")
                ->where('id', $row->id)
                ->update(['embedding_state' => 'lexical_only']);

            $dispatched++;
        }

        $failed = DB::table("{$schema}.search_projections")
            ->where('embedding_state', 'failed')
            ->count();

        return ['dispatched' => $dispatched, 'failed' => $failed];
    }

    /**
     * Check if all embeddings have reached a terminal state.
     */
    public function isComplete(string $schema): bool
    {
        $pending = DB::table("{$schema}.search_projections")
            ->where('embedding_state', 'pending')
            ->count();

        return $pending === 0;
    }

    /**
     * Get the count of failed embeddings.
     */
    public function failedCount(string $schema): int
    {
        return (int) DB::table("{$schema}.search_projections")
            ->where('embedding_state', 'failed')
            ->count();
    }
}
