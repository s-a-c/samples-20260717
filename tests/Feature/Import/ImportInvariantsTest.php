<?php

declare(strict_types=1);

use App\Services\ProductImport\ImportInvariants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(ImportInvariants::class);

uses(RefreshDatabase::class);

test('invariants pass when portfolio view exists', function () {
    $invariants = app(ImportInvariants::class);
    $result = $invariants->evaluate('chinook');

    expect($result['passed'])->toBeTrue()
        ->and($result['failures'])->toBeEmpty();
});

test('invariants fail when portfolio view is missing', function () {
    DB::statement('DROP VIEW IF EXISTS public.product_portfolio_snapshots');

    $invariants = app(ImportInvariants::class);
    $result = $invariants->evaluate('chinook');

    expect($result['passed'])->toBeFalse()
        ->and($result['failures'])->not->toBeEmpty();
});

test('invariants fail when domain data has no search projections', function () {
    DB::table('chinook.search_projections')->delete();
    DB::table('chinook.artists')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'name' => 'Unprojected Artist',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('chinook.search_projections')->delete();

    $result = app(ImportInvariants::class)->evaluate('chinook');

    expect($result['passed'])->toBeFalse()
        ->and($result['failures'])->toContain('Expected search projection rows in chinook but found none despite having domain data');
});
