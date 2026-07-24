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
        Schema::create('reset_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product');
            $table->string('kind'); // import|reset|recover|dry_run
            $table->string('status'); // pending|running|succeeded|failed|recovering
            $table->string('current_phase')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->uuid('recovery_of')->nullable();
            $table->timestamps();
        });

        Schema::table('reset_runs', function (Blueprint $table) {
            $table->foreign('recovery_of')->references('id')->on('reset_runs')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX idx_reset_runs_active
                ON reset_runs (product)
                WHERE status IN ('pending', 'running', 'recovering');
            ");
        } else {
            Schema::table('reset_runs', function (Blueprint $table) {
                $table->index(['product', 'status'], 'idx_reset_runs_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reset_runs');
    }
};
