<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE northwind.categories ALTER COLUMN picture TYPE bytea USING picture::bytea;');
        DB::statement('ALTER TABLE northwind.employees ALTER COLUMN photo TYPE bytea USING photo::bytea;');
        DB::statement('ALTER TABLE pagila.staff ALTER COLUMN picture TYPE bytea USING picture::bytea;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE northwind.categories ALTER COLUMN picture TYPE text USING picture::text;');
        DB::statement('ALTER TABLE northwind.employees ALTER COLUMN photo TYPE text USING photo::text;');
        DB::statement('ALTER TABLE pagila.staff ALTER COLUMN picture TYPE text USING picture::text;');
    }
};
