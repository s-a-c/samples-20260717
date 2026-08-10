<?php

declare(strict_types=1);

use App\Services\ProductImport\Schema\SearchProjectionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SearchProjectionSchema::createTable('pagila');

        SearchProjectionSchema::createTriggers('pagila', [
            'films' => ['type' => 'film', 'd' => 'NEW.title', 'c' => 'NEW.description'],
            'actors' => ['type' => 'actor', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NULL'],
            'categories' => ['type' => 'category', 'd' => 'NEW.name', 'c' => 'NULL'],
            'customers' => ['type' => 'customer', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.email'],
            'staff' => ['type' => 'staff', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.email'],
            'stores' => ['type' => 'store', 'd' => "'Store ' || NEW.id::text", 'c' => 'NULL'],
            'languages' => ['type' => 'language', 'd' => 'NEW.name', 'c' => 'NULL'],
            'cities' => ['type' => 'city', 'd' => 'NEW.city', 'c' => 'NULL'],
            'countries' => ['type' => 'country', 'd' => 'NEW.country', 'c' => 'NULL'],
        ]);
    }

    public function down(): void
    {
        SearchProjectionSchema::drop('pagila');
    }
};
