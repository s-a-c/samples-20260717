<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates pagila.addresses with the expected columns and FK to cities', function () {
    expect(Schema::hasTable('pagila.addresses'))->toBeTrue();

    expect(Schema::hasColumns('pagila.addresses', [
        'id', 'address', 'address2', 'district', 'city_id', 'postal_code', 'phone', 'created_at', 'updated_at',
    ]))->toBeTrue();

    $fks = DB::select("
        SELECT conname FROM pg_constraint
        WHERE conrelid = 'pagila.addresses'::regclass AND contype = 'f'
    ");
    expect(collect($fks)->pluck('conname')->contains(fn (mixed $n): bool => is_string($n) && str_contains($n, 'city_id')))->toBeTrue();
});

it('relinks staff, customers, stores to address_id FK and drops the flat address column', function () {
    foreach (['staff', 'customers', 'stores'] as $table) {
        expect(Schema::hasColumn("pagila.{$table}", 'address_id'))->toBeTrue("pagila.{$table} should have address_id");
        expect(Schema::hasColumn("pagila.{$table}", 'address'))->toBeFalse("pagila.{$table} should NOT have flat address column");
    }
});
