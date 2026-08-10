<?php

declare(strict_types=1);

use App\Services\ProductImport\Schema\SearchProjectionSchema;
use Illuminate\Database\Migrations\Migration;

/**
 * Create chinook search projections table and schema-aware triggers.
 *
 * Trigger functions use TG_TABLE_SCHEMA so they work correctly in both
 * live (chinook) and staging (chinook_staging) schemas.
 */
return new class extends Migration
{
    public function up(): void
    {
        SearchProjectionSchema::createTable('chinook');

        SearchProjectionSchema::createTriggers('chinook', [
            'artists' => ['type' => 'artist', 'd' => 'NEW.name', 'c' => 'NULL'],
            'albums' => ['type' => 'album', 'd' => 'NEW.title', 'c' => 'NULL'],
            'tracks' => ['type' => 'track', 'd' => 'NEW.name', 'c' => 'NEW.composer'],
            'customers' => ['type' => 'customer', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.company'],
            'employees' => ['type' => 'employee', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.title'],
            'playlists' => ['type' => 'playlist', 'd' => 'NEW.name', 'c' => 'NULL'],
            'genres' => ['type' => 'genre', 'd' => 'NEW.name', 'c' => 'NULL'],
            'media_types' => ['type' => 'media_type', 'd' => 'NEW.name', 'c' => 'NULL'],
            'invoices' => ['type' => 'invoice', 'd' => "'Invoice #' || NEW.id::text", 'c' => 'NULL'],
        ]);
    }

    public function down(): void
    {
        SearchProjectionSchema::drop('chinook');
    }
};
