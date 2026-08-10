<?php

declare(strict_types=1);

use App\Domain\Staging\Chinook\Artist as StagingArtist;
use App\Models\Chinook\Artist as LiveArtist;
use App\Models\ResetRun;
use App\Services\ProductImport\StagingContext;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

covers(StagingArtist::class, StagingContext::class);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
});

test('staging model can write during an active reset run', function () {
    $builder = app(StagingSchemaBuilder::class);
    $builder->build('chinook');

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $context = app(StagingContext::class);
    $artist = $context->run(fn () => StagingArtist::create([
        'name' => 'Test Staging Artist',
    ]));

    expect($artist->exists)->toBeTrue()
        ->and($artist->name)->toBe('Test Staging Artist');
});

test('live model is blocked during an active reset run', function () {
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    expect(fn () => LiveArtist::create(['name' => 'Should Fail']))
        ->toThrow(App\Exceptions\ProductResetWindowOpen::class);
});

test('staging model does not use BelongsToProductDomain trait', function () {
    expect(StagingArtist::class)
        ->not->toUse(App\Traits\BelongsToProductDomain::class);
});

test('live model uses BelongsToProductDomain trait', function () {
    expect(LiveArtist::class)
        ->toUse(App\Traits\BelongsToProductDomain::class);
});
