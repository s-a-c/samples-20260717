<?php

declare(strict_types=1);

use App\Models\ResetRun;
use App\Services\ProductImport\SourceIdentityRegistry;

covers(SourceIdentityRegistry::class);

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('source identity registry works during an active reset run', function () {
    // SourceIdentity model does NOT use BelongsToProductDomain, so it can write during a run
    ResetRun::create([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $registry = app(SourceIdentityRegistry::class);

    $uuid1 = $registry->getOrMint('chinook.artists', ['id' => 5]);
    $uuid2 = $registry->getOrMint('chinook.artists', ['id' => 5]);

    expect($uuid1)->toBe($uuid2)
        ->and($uuid1)->toBeString();
});

test('source identity registry returns different uuids for different source keys', function () {
    $registry = app(SourceIdentityRegistry::class);

    $uuid1 = $registry->getOrMint('chinook.artists', ['id' => 1]);
    $uuid2 = $registry->getOrMint('chinook.artists', ['id' => 2]);

    expect($uuid1)->not->toBe($uuid2);
});
