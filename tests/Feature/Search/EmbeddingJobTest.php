<?php

declare(strict_types=1);

use App\Jobs\EmbeddingJob;
use App\Models\Chinook\Artist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(EmbeddingJob::class);

uses(RefreshDatabase::class);

test('embedding job handles pending projection row and generates vector embedding', function () {
    $artist = Artist::create(['name' => 'Pink Floyd']);

    $job = new EmbeddingJob('chinook', $artist->id);
    $job->handle();

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
