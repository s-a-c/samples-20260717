<?php

declare(strict_types=1);

use App\Jobs\ProductImportJob;
use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

covers(ProductImportJob::class);

test('job dispatches with correct product name', function () {
    Queue::fake();

    ProductImportJob::dispatch('chinook');

    Queue::assertPushed(ProductImportJob::class, function (ProductImportJob $job) {
        return $job->product === 'chinook';
    });
});

test('job calls pipeline run with product', function () {
    $pipeline = Mockery::mock(ProductImportPipeline::class);
    $pipeline->shouldReceive('run')
        ->once()
        ->with('chinook', false)
        ->andReturn(['success' => true]);

    app()->instance(ProductImportPipeline::class, $pipeline);

    $job = new ProductImportJob('chinook');
    app()->call([$job, 'handle']);
});

test('job handles pipeline failure without re-throwing', function () {
    $pipeline = Mockery::mock(ProductImportPipeline::class);
    $pipeline->shouldReceive('run')
        ->once()
        ->with('northwind', false)
        ->andReturn(['success' => false, 'error' => 'Source file not found']);

    app()->instance(ProductImportPipeline::class, $pipeline);

    $job = new ProductImportJob('northwind');
    app()->call([$job, 'handle']);
});

test('job does not require SerializesModels', function () {
    $job = new ProductImportJob('pagila');
    $serialized = serialize($job);
    $restored = unserialize($serialized);

    expect($restored->product)->toBe('pagila');
});
