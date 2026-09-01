<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('postgres extensions vector unaccent and pg_trgm are installed', function () {
    $extensions = DB::select('SELECT extname FROM pg_extension');
    $names = array_column($extensions, 'extname');

    expect($names)->toContain('vector', 'unaccent', 'pg_trgm');
});

test('en_unaccent text search configuration exists', function () {
    $configs = DB::select("SELECT cfgname FROM pg_ts_config WHERE cfgname = 'en_unaccent'");
    expect($configs)->not->toBeEmpty();
});

test('pgsql check command reports healthy', function () {
    $this->artisan('pgsql:check')
        ->expectsOutputToContain('PostgreSQL extensions and text search configuration healthy.')
        ->assertExitCode(0);
});

test('postgres direct connection options preserve zero-valued environment values', function () {
    putenv('DB_DIRECT_PASSWORD=0');

    try {
        $database = require config_path('database.php');

        if (! is_array($database)) {
            throw new RuntimeException('Database configuration must be an array.');
        }

        $connections = $database['connections'] ?? null;
        if (! is_array($connections)) {
            throw new RuntimeException('Database connections configuration is missing.');
        }

        $pgsql = $connections['pgsql'] ?? null;
        if (! is_array($pgsql)) {
            throw new RuntimeException('PostgreSQL configuration is missing.');
        }

        $direct = $pgsql['direct'] ?? null;
        if (! is_array($direct)) {
            throw new RuntimeException('PostgreSQL direct configuration is missing.');
        }

        expect($direct['password'] ?? null)->toBe('0');
    } finally {
        putenv('DB_DIRECT_PASSWORD');
    }
});
