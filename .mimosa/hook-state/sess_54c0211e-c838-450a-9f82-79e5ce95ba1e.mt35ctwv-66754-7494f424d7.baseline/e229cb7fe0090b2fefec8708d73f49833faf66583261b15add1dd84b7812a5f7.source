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
