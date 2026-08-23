<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Northwind\NorthwindProductMapper;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

covers(
    NorthwindProductMapper::class,
    App\Services\ProductImport\Mapping\Northwind\CategoryMapper::class,
    App\Services\ProductImport\Mapping\Northwind\SupplierMapper::class,
    App\Services\ProductImport\Mapping\Northwind\EmployeeMapper::class,
    App\Services\ProductImport\Mapping\Northwind\CustomerMapper::class,
    App\Services\ProductImport\Mapping\Northwind\ProductMapper_::class,
    SourceSchemaBuilder::class
);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS northwind_source CASCADE');
    DB::statement('DROP SCHEMA IF EXISTS northwind_staging CASCADE');
});

beforeEach(function () {
    SourceSchemaBuilder::buildNorthwind();
    $sql = File::get(base_path('tests/Fixtures/Sources/northwind/minimal.sql'));
    $lines = explode("\n", $sql);
    $codeLines = array_filter($lines, fn (string $line): bool => ! str_starts_with(mb_trim($line), '--'));
    $cleanSql = implode("\n", $codeLines);
    foreach (array_filter(array_map('trim', explode(';', $cleanSql)), fn (string $statement): bool => $statement !== '') as $statement) {
        if ($statement !== '') {
            DB::statement($statement);
        }
    }
    app(StagingSchemaBuilder::class)->build('northwind');
});

test('northwind transform loads categories with UUID PKs', function () {
    $mapper = new NorthwindProductMapper;
    $result = $mapper->load('northwind_source', 'northwind_staging');

    $categories = DB::table('northwind_staging.categories')->get();
    expect($categories)->toHaveCount(2);
});

test('northwind transform resolves product supplier and category FKs', function () {
    $mapper = new NorthwindProductMapper;
    $mapper->load('northwind_source', 'northwind_staging');

    $products = DB::table('northwind_staging.products')->get();
    expect($products)->toHaveCount(2);

    $supplierIds = DB::table('northwind_staging.suppliers')->pluck('id')->all();
    $categoryIds = DB::table('northwind_staging.categories')->pluck('id')->all();

    foreach ($products as $product) {
        expect($supplierIds)->toContain($product->supplier_id)
            ->and($categoryIds)->toContain($product->category_id);
    }
});

test('northwind transform handles string customer IDs', function () {
    $mapper = new NorthwindProductMapper;
    $mapper->load('northwind_source', 'northwind_staging');

    $customers = DB::table('northwind_staging.customers')->get();
    expect($customers)->toHaveCount(2);

    foreach ($customers as $customer) {
        expect($customer->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    }
});

test('northwind transform resolves employee self-referential FK', function () {
    $mapper = new NorthwindProductMapper;
    $mapper->load('northwind_source', 'northwind_staging');

    $employees = DB::table('northwind_staging.employees')->get();
    expect($employees)->toHaveCount(2);

    $andrew = $employees->firstWhere('last_name', 'Fuller');
    $nancy = $employees->firstWhere('last_name', 'Davolio');

    expect($andrew->reports_to)->toBeNull();
    expect($nancy->reports_to)->not->toBeNull()
        ->and($nancy->reports_to)->toBe($andrew->id);
});

test('northwind mapper exposes its ordered table mappers', function () {
    $method = new ReflectionMethod(NorthwindProductMapper::class, 'mappers');
    $mappers = $method->invoke(new NorthwindProductMapper);

    expect($mappers)->toHaveCount(5);
});

test('northwind transform preserves nullable product foreign keys', function () {
    DB::table('northwind_source.products')->insert([
        'product_id' => 99,
        'product_name' => 'Unlinked Product',
        'supplier_id' => null,
        'category_id' => null,
        'quantity_per_unit' => null,
        'unit_price' => 0,
        'units_in_stock' => 0,
        'units_on_order' => 0,
        'reorder_level' => 0,
        'discontinued' => 0,
    ]);

    (new NorthwindProductMapper)->load('northwind_source', 'northwind_staging');

    $product = DB::table('northwind_staging.products')->where('product_name', 'Unlinked Product')->first();
    expect($product->supplier_id)->toBeNull()->and($product->category_id)->toBeNull();
});
