<?php

declare(strict_types=1);

use App\Domain\Staging\Chinook\Genre;
use App\Jobs\EmbeddingJob;
use App\Services\ProductImport\StagingContext;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

covers(StagingContext::class);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
});

test('staging context suppresses embedding job dispatch', function () {
    $builder = app(StagingSchemaBuilder::class);
    $builder->build('chinook');

    Queue::fake();

    $context = app(StagingContext::class);

    $context->run(function () {
        Genre::create(['name' => 'Test Genre']);
    });

    Queue::assertNotPushed(EmbeddingJob::class);
});

test('staging context reactivates observers after closure', function () {
    $context = app(StagingContext::class);

    $context->run(fn () => null);

    expect(app()->bound('is_staging'))->toBeFalse();
});

test('staging context deactivates even on exception', function () {
    $context = app(StagingContext::class);

    try {
        $context->run(function () {
            throw new RuntimeException('Test failure');
        });
    } catch (RuntimeException) {
        // Expected
    }

    expect(app()->bound('is_staging'))->toBeFalse();
});
