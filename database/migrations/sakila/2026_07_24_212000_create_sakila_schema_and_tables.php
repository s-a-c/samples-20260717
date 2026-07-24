<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS sakila CASCADE;');
        DB::statement('CREATE SCHEMA IF NOT EXISTS sakila;');

        Schema::create('sakila.actors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();
        });

        Schema::create('sakila.categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sakila.countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country');
            $table->timestamps();
        });

        Schema::create('sakila.cities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('city');
            $table->foreignUuid('country_id')->references('id')->on('sakila.countries')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sakila.languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sakila.stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('manager_staff_id')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('sakila.staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address')->nullable();
            $table->text('picture')->nullable();
            $table->string('email')->nullable();
            $table->foreignUuid('store_id')->nullable();
            $table->boolean('active')->default(true);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sakila.stores ADD CONSTRAINT stores_manager_staff_id_foreign FOREIGN KEY (manager_staff_id) REFERENCES sakila.staff(id) ON DELETE SET NULL DEFERRABLE INITIALLY DEFERRED;');
        DB::statement('ALTER TABLE sakila.staff ADD CONSTRAINT staff_store_id_foreign FOREIGN KEY (store_id) REFERENCES sakila.stores(id) ON DELETE SET NULL DEFERRABLE INITIALLY DEFERRED;');

        Schema::create('sakila.customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->references('id')->on('sakila.stores')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sakila.films', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('release_year')->nullable();
            $table->foreignUuid('language_id')->references('id')->on('sakila.languages')->cascadeOnDelete();
            $table->foreignUuid('original_language_id')->nullable()->references('id')->on('sakila.languages')->nullOnDelete();
            $table->integer('rental_duration')->default(3);
            $table->decimal('rental_rate', 10, 2)->default(4.99);
            $table->integer('length')->nullable();
            $table->decimal('replacement_cost', 10, 2)->default(19.99);
            $table->string('rating')->nullable();
            $table->text('special_features')->nullable();
            $table->timestamps();
        });

        Schema::create('sakila.film_actors', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('film_id')->references('id')->on('sakila.films')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->references('id')->on('sakila.actors')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sakila.film_categories', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('film_id')->references('id')->on('sakila.films')->cascadeOnDelete();
            $table->foreignUuid('category_id')->references('id')->on('sakila.categories')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sakila.film_texts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('film_id')->nullable()->references('id')->on('sakila.films')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sakila.inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('film_id')->references('id')->on('sakila.films')->cascadeOnDelete();
            $table->foreignUuid('store_id')->references('id')->on('sakila.stores')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sakila.rentals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('rental_date');
            $table->foreignUuid('inventory_id')->references('id')->on('sakila.inventories')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->references('id')->on('sakila.customers')->cascadeOnDelete();
            $table->timestamp('return_date')->nullable();
            $table->foreignUuid('staff_id')->references('id')->on('sakila.staff')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sakila.payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->references('id')->on('sakila.customers')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->references('id')->on('sakila.staff')->cascadeOnDelete();
            $table->foreignUuid('rental_id')->nullable()->references('id')->on('sakila.rentals')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('payment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS sakila CASCADE;');
    }
};
