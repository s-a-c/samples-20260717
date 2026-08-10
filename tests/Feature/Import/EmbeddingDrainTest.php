<?php

declare(strict_types=1);

use App\Services\ProductImport\EmbeddingDrain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(EmbeddingDrain::class);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
});

test('drain marks pending embeddings as lexical_only', function () {
    // Insert a pending projection
    DB::table('chinook.search_projections')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'entity_type' => 'test',
        'weight_d_text' => 'test text',
        'embedding_state' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $drain = app(EmbeddingDrain::class);
    $result = $drain->drain('chinook');

    expect($result['dispatched'])->toBe(1)
        ->and($result['failed'])->toBe(0);
});

test('is complete returns true when no pending rows remain', function () {
    $drain = app(EmbeddingDrain::class);
    expect($drain->isComplete('chinook'))->toBeTrue();
});

test('is complete returns false when pending rows exist', function () {
    DB::table('chinook.search_projections')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'entity_type' => 'test',
        'embedding_state' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $drain = app(EmbeddingDrain::class);
    expect($drain->isComplete('chinook'))->toBeFalse();
});

test('failed count returns number of failed embeddings', function () {
    DB::table('chinook.search_projections')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'entity_type' => 'test',
        'embedding_state' => 'failed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $drain = app(EmbeddingDrain::class);
    expect($drain->failedCount('chinook'))->toBe(1);
});
