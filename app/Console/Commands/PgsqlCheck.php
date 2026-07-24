<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PgsqlCheck extends Command
{
    protected $signature = 'pgsql:check';

    protected $description = 'Verify PostgreSQL extensions and text search configuration health';

    public function handle(): int
    {
        $extensions = array_column(DB::select('SELECT extname FROM pg_extension'), 'extname');
        $required = ['vector', 'unaccent', 'pg_trgm'];

        foreach ($required as $ext) {
            if (! in_array($ext, $extensions, true)) {
                $this->error("Missing extension: {$ext}");

                return 1;
            }
        }

        $ts = DB::select("SELECT cfgname FROM pg_ts_config WHERE cfgname = 'en_unaccent'");
        if (empty($ts)) {
            $this->error('Missing text search config: en_unaccent');

            return 1;
        }

        $this->info('PostgreSQL extensions and text search configuration healthy.');

        return 0;
    }
}
