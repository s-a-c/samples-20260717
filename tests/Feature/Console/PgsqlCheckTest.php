<?php

declare(strict_types=1);

use App\Console\Commands\PgsqlCheck;
use Illuminate\Support\Facades\DB;

covers(PgsqlCheck::class);

test('pgsql check reports healthy extensions and text search config', function () {
    $this->artisan('pgsql:check')
        ->assertSuccessful()
        ->expectsOutput('PostgreSQL extensions and text search configuration healthy.');
});

test('pgsql check fails when a required extension is missing', function () {
    DB::shouldReceive('select')
        ->andReturnUsing(function (string $query, array $bindings = []) {
            if (str_contains($query, 'pg_extension')) {
                return [
                    (object) ['extname' => 'plpgsql'],
                    (object) ['extname' => 'unaccent'],
                ];
            }

            return [];
        });

    $this->artisan('pgsql:check')
        ->assertFailed()
        ->expectsOutput('Missing extension: vector');
});

test('pgsql check fails when the text search config is missing', function () {
    DB::shouldReceive('select')
        ->andReturnUsing(function (string $query, array $bindings = []) {
            if (str_contains($query, 'pg_extension')) {
                return [
                    (object) ['extname' => 'vector'],
                    (object) ['extname' => 'unaccent'],
                    (object) ['extname' => 'pg_trgm'],
                ];
            }

            return [];
        });

    $this->artisan('pgsql:check')
        ->assertFailed()
        ->expectsOutput('Missing text search config: en_unaccent');
});
