<?php

declare(strict_types=1);

use App\Console\Commands\PgsqlCheck;

covers(PgsqlCheck::class);

test('pgsql check reports healthy extensions and text search config', function () {
    $this->artisan('pgsql:check')
        ->assertSuccessful()
        ->expectsOutput('PostgreSQL extensions and text search configuration healthy.');
});
