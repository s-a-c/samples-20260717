<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('source_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity');
            $table->jsonb('source_key');
            $table->uuid('domain_id');
            $table->timestamps();

            $table->unique(['entity', 'source_key']);
        });

        DB::statement("
            ALTER TABLE source_identities
            ADD COLUMN product text GENERATED ALWAYS AS (split_part(entity, '.', 1)) STORED;
        ");

        DB::statement("
            ALTER TABLE source_identities
            ADD CONSTRAINT source_identities_entity_check
            CHECK (entity ~ '^(chinook|northwind|sakila)\\.[a-z_][a-z0-9_]*$');
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_identities');
    }
};
