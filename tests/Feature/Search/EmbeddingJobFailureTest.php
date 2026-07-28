<?php

declare(strict_types=1);

use App\Jobs\EmbeddingJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;

covers(EmbeddingJob::class);

test('embedding job marks the projection failed when the ai provider always throws', function (): void {
    // Insert a projection row directly so the Tier1SourceObserver does not
    // dispatch the job during model creation (QUEUE_CONNECTION=sync).
    $id = (string) Str::uuid();
    DB::statement(<<<'SQL'
        INSERT INTO chinook.search_projections
            (id, entity_type, weight_d_text, weight_c_text, weight_b_text, weight_a_text, embedding_state)
        VALUES
            (?, 'artist', 'Title', 'Description', 'Category', '1', 'pending')
    SQL, [$id]);

    // Every provider call throws — simulating an unreachable / misconfigured provider.
    Embeddings::fake(fn () => throw new RuntimeException('provider down'));

    $job = new EmbeddingJob('chinook', $id);

    // Exhaust the configured retries the way the queue would (it catches Throwable).
    for ($attempt = 0; $attempt < $job->tries; $attempt++) {
        try {
            $job->handle();
        } catch (Throwable) {
            // Expected: the exception must propagate so the queue retries.
        }
    }

    // The queue then invokes failed() after the final attempt.
    $job->failed(new RuntimeException('provider down'));

    $row = DB::selectOne(
        'SELECT embedding_state, embedding FROM chinook.search_projections WHERE id = ?',
        [$id],
    );

    expect($row->embedding_state)->toBe('failed');
    // No synthetic vector is ever written for a failed embedding.
    expect($row->embedding)->toBeNull();
});

test('embedding job has exponential backoff configured per the failure contract', function (): void {
    $job = new EmbeddingJob('chinook', (string) Str::uuid());

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([10, 30, 90]);
});
