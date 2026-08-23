<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagila.addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('address');
            $table->text('address2')->nullable();
            $table->text('district')->nullable();
            $table->uuid('city_id');
            $table->foreign('city_id')->references('id')->on('pagila.cities')->cascadeOnDelete();
            $table->text('postal_code')->nullable();
            $table->text('phone')->nullable();
            $table->timestamps();
        });

        foreach (['staff', 'customers', 'stores'] as $table) {
            Schema::table("pagila.{$table}", function (Blueprint $t) {
                $t->dropColumn('address');
                $t->uuid('address_id')->nullable()->after('id');
                $t->foreign('address_id')->references('id')->on('pagila.addresses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['staff', 'customers', 'stores'] as $table) {
            Schema::table("pagila.{$table}", function (Blueprint $t) use ($table) {
                $t->dropForeign(["{$table}_address_id_foreign"]);
                $t->dropColumn('address_id');
                $t->text('address')->nullable()->after('id');
            });
        }

        Schema::dropIfExists('pagila.addresses');
    }
};
