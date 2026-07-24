<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');
        DB::statement('CREATE SCHEMA IF NOT EXISTS chinook;');

        Schema::create('chinook.artists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('chinook.albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('artist_id')->references('id')->on('chinook.artists')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chinook.genres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('chinook.media_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('chinook.employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('title')->nullable();
            $table->foreignUuid('reports_to')->nullable();
            $table->timestamp('birth_date')->nullable();
            $table->timestamp('hire_date')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::table('chinook.employees', function (Blueprint $table) {
            $table->foreign('reports_to')->references('id')->on('chinook.employees')->nullOnDelete();
        });

        Schema::create('chinook.customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email');
            $table->foreignUuid('support_rep_id')->nullable()->references('id')->on('chinook.employees')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('chinook.tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('album_id')->nullable()->references('id')->on('chinook.albums')->cascadeOnDelete();
            $table->foreignUuid('media_type_id')->references('id')->on('chinook.media_types')->cascadeOnDelete();
            $table->foreignUuid('genre_id')->nullable()->references('id')->on('chinook.genres')->nullOnDelete();
            $table->string('composer')->nullable();
            $table->integer('milliseconds');
            $table->integer('bytes')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('chinook.playlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('chinook.playlist_track', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('playlist_id')->references('id')->on('chinook.playlists')->cascadeOnDelete();
            $table->foreignUuid('track_id')->references('id')->on('chinook.tracks')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chinook.invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->references('id')->on('chinook.customers')->cascadeOnDelete();
            $table->timestamp('invoice_date');
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('chinook.invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->references('id')->on('chinook.invoices')->cascadeOnDelete();
            $table->foreignUuid('track_id')->references('id')->on('chinook.tracks')->cascadeOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');
    }
};
