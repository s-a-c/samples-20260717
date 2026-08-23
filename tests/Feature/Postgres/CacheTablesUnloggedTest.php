<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores cache and cache_locks as unlogged tables', function () {
    $cache = DB::selectOne("SELECT relpersistence FROM pg_class WHERE relname = 'cache'")->relpersistence;
    $cacheLocks = DB::selectOne("SELECT relpersistence FROM pg_class WHERE relname = 'cache_locks'")->relpersistence;

    expect($cache)->toBe('u')
        ->and($cacheLocks)->toBe('u');
});

it('keeps session and queue tables logged', function () {
    $persistence = DB::select("
        SELECT relname, relpersistence
        FROM pg_class
        WHERE relname IN ('sessions', 'jobs', 'job_batches', 'failed_jobs')
    ");

    expect($persistence)->toHaveCount(4);

    foreach ($persistence as $table) {
        expect($table->relpersistence)->toBe('p');
    }
});
