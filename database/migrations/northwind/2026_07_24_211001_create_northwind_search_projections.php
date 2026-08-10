<?php

declare(strict_types=1);

use App\Services\ProductImport\Schema\SearchProjectionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SearchProjectionSchema::createTable('northwind');

        SearchProjectionSchema::createTriggers('northwind', [
            'products' => ['type' => 'product', 'd' => 'NEW.product_name', 'c' => 'NULL'],
            'categories' => ['type' => 'category', 'd' => 'NEW.category_name', 'c' => 'NEW.description'],
            'customers' => ['type' => 'customer', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
            'employees' => ['type' => 'employee', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.title'],
            'suppliers' => ['type' => 'supplier', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
            'shippers' => ['type' => 'shipper', 'd' => 'NEW.company_name', 'c' => 'NULL'],
            'orders' => ['type' => 'order', 'd' => 'NEW.ship_name', 'c' => 'NULL'],
            'regions' => ['type' => 'region', 'd' => 'NEW.region_description', 'c' => 'NULL'],
            'territories' => ['type' => 'territory', 'd' => 'NEW.territory_description', 'c' => 'NULL'],
        ]);
    }

    public function down(): void
    {
        SearchProjectionSchema::drop('northwind');
    }
};
