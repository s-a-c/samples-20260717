<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS northwind CASCADE;');
        DB::statement('CREATE SCHEMA IF NOT EXISTS northwind;');

        Schema::create('northwind.categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->text('picture')->nullable();
            $table->timestamps();
        });

        Schema::create('northwind.customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->timestamps();
        });

        Schema::create('northwind.employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('title')->nullable();
            $table->string('title_of_courtesy')->nullable();
            $table->timestamp('birth_date')->nullable();
            $table->timestamp('hire_date')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('extension')->nullable();
            $table->text('photo')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('reports_to')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::table('northwind.employees', function (Blueprint $table) {
            $table->foreign('reports_to')->references('id')->on('northwind.employees')->nullOnDelete();
        });

        Schema::create('northwind.regions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('region_description');
            $table->timestamps();
        });

        Schema::create('northwind.shippers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('northwind.suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->text('homepage')->nullable();
            $table->timestamps();
        });

        Schema::create('northwind.territories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('territory_description');
            $table->foreignUuid('region_id')->references('id')->on('northwind.regions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('northwind.employee_territories', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('employee_id')->references('id')->on('northwind.employees')->cascadeOnDelete();
            $table->foreignUuid('territory_id')->references('id')->on('northwind.territories')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('northwind.products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product_name');
            $table->foreignUuid('supplier_id')->nullable()->references('id')->on('northwind.suppliers')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->references('id')->on('northwind.categories')->nullOnDelete();
            $table->string('quantity_per_unit')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('units_in_stock')->default(0);
            $table->integer('units_on_order')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->boolean('discontinued')->default(false);
            $table->timestamps();
        });

        Schema::create('northwind.orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->nullable()->references('id')->on('northwind.customers')->nullOnDelete();
            $table->foreignUuid('employee_id')->nullable()->references('id')->on('northwind.employees')->nullOnDelete();
            $table->timestamp('order_date')->nullable();
            $table->timestamp('required_date')->nullable();
            $table->timestamp('shipped_date')->nullable();
            $table->foreignUuid('ship_via')->nullable()->references('id')->on('northwind.shippers')->nullOnDelete();
            $table->decimal('freight', 10, 2)->default(0);
            $table->string('ship_name')->nullable();
            $table->string('ship_address')->nullable();
            $table->string('ship_city')->nullable();
            $table->string('ship_region')->nullable();
            $table->string('ship_postal_code')->nullable();
            $table->string('ship_country')->nullable();
            $table->timestamps();
        });

        Schema::create('northwind.order_details', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('order_id')->references('id')->on('northwind.orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->references('id')->on('northwind.products')->cascadeOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('discount', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS northwind CASCADE;');
    }
};
