<?php

declare(strict_types=1);

use App\Jobs\EmbeddingJob;
use App\Models\Chinook\Artist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use RuntimeException;

covers(EmbeddingJob::class);

uses(RefreshDatabase::class);

test('embedding job handles pending projection row and generates vector embedding', function () {
    Embeddings::fake();

    $artist = Artist::create(['name' => 'Pink Floyd']);

    $job = new EmbeddingJob('chinook', $artist->id);
    $job->handle();

    // The embedding was generated through the AI SDK, not the raw HTTP fallback.
    Embeddings::assertGenerated(fn () => true);

    $projection = DB::selectOne('SELECT embedding, embedding_profile, content_digest, embedded_at, embedding_state FROM chinook.search_projections WHERE id = ?', [$artist->id]);

    expect($projection)->not->toBeNull();
    expect($projection->embedding_state)->toBe('complete');
    expect($projection->embedding_profile)->toBe('openai:text-embedding-3-small:1024');
    expect($projection->content_digest)->toBe(hash('sha256', "Pink Floyd {$artist->id}"));
    expect($projection->embedded_at)->not->toBeNull();
    expect($projection->embedding)->not->toBeNull();

    $vectorDim = DB::selectOne('SELECT vector_dims(embedding) as dim FROM chinook.search_projections WHERE id = ?', [$artist->id]);
    expect((int) $vectorDim->dim)->toBe(1024);
});

test('embedding job returns early when no projection row exists for the entity', function () {
    Embeddings::fake();

    $missingId = (string) Str::uuid();

    $job = new EmbeddingJob('chinook', $missingId);
    $job->handle();

    $row = DB::selectOne('SELECT embedding_state FROM chinook.search_projections WHERE id = ?', [$missingId]);

    expect($row)->toBeNull();
});

test('embedding job throws when the provider returns an empty vector', function () {
    $id = (string) Str::uuid();
    DB::statement(<<<'SQL'
        INSERT INTO chinook.search_projections
            (id, entity_type, weight_d_text, weight_c_text, weight_b_text, weight_a_text, embedding_state)
        VALUES
            (?, 'artist', 'Title', 'Description', 'Category', '1', 'pending')
    SQL, [$id]);

    Embeddings::fake(fn () => [[]]);

    $job = new EmbeddingJob('chinook', $id);

    expect(fn () => $job->handle())->toThrow(RuntimeException::class, 'Embedding provider returned an empty vector.');
});
