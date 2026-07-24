<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'en_unaccent') THEN
                    CREATE TEXT SEARCH CONFIGURATION en_unaccent (COPY = english);
                    ALTER TEXT SEARCH CONFIGURATION en_unaccent
                        ALTER MAPPING FOR word, asciiword, hword, numword WITH unaccent, english_stem;
                END IF;
            END
            $$;
        ");
    }

    public function down(): void
    {
        // No-op by design (extensions are infrastructure)
    }
};
