<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Northwind;

use App\Domain\Staging\Northwind\Category as StagingCategory;
use App\Domain\Staging\Northwind\Customer as StagingCustomer;
use App\Domain\Staging\Northwind\Employee as StagingEmployee;
use App\Domain\Staging\Northwind\Product as StagingProduct;
use App\Domain\Staging\Northwind\Supplier as StagingSupplier;
use App\Services\ProductImport\Mapping\ProductMapper;
use App\Services\ProductImport\Mapping\SelfReferentialMapper;
use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use App\Services\ProductImport\StagingContext;
use Illuminate\Support\Facades\DB;

class NorthwindProductMapper extends ProductMapper
{
    public function load(string $sourceSchema, string $stagingSchema): array
    {
        $registry = app(SourceIdentityRegistry::class);
        $context = app(StagingContext::class);

        $mappers = [
            new CategoryMapper($registry),
            new SupplierMapper($registry),
            new EmployeeMapper($registry),
            new CustomerMapper($registry),
            new ProductMapper_($registry),
        ];

        $totalRows = 0;

        $context->run(function () use ($mappers, $sourceSchema, $stagingSchema, &$totalRows) {
            foreach ($mappers as $mapper) {
                $totalRows += $mapper->load($sourceSchema, $stagingSchema);
            }
        });

        return ['tables' => count($mappers), 'rows' => $totalRows];
    }

    protected function mappers(): array
    {
        $registry = app(SourceIdentityRegistry::class);

        return [
            new CategoryMapper($registry),
            new SupplierMapper($registry),
            new EmployeeMapper($registry),
            new CustomerMapper($registry),
            new ProductMapper_($registry),
        ];
    }
}

class CategoryMapper extends TableMapper
{
    public function __construct(private SourceIdentityRegistry $registry) {}

    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.categories")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.categories', ['CategoryID' => $row->category_id]);
            StagingCategory::create(['id' => $uuid, 'category_name' => $row->category_name ?? '', 'description' => $row->description]);
            $count++;
        }

        return $count;
    }
}

class SupplierMapper extends TableMapper
{
    public function __construct(private SourceIdentityRegistry $registry) {}

    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.suppliers")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.suppliers', ['SupplierID' => $row->supplier_id]);
            StagingSupplier::create([
                'id' => $uuid,
                'company_name' => $row->company_name ?? '',
                'contact_name' => $row->contact_name,
                'contact_title' => $row->contact_title,
                'address' => $row->address,
                'city' => $row->city,
                'region' => $row->region,
                'postal_code' => $row->postal_code,
                'country' => $row->country,
                'phone' => $row->phone,
                'fax' => $row->fax,
                'homepage' => $row->homepage,
            ]);
            $count++;
        }

        return $count;
    }
}

class EmployeeMapper extends SelfReferentialMapper
{
    public function __construct(private SourceIdentityRegistry $registry) {}

    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.employees")->orderBy('employee_id')->get();
        $count = 0;
        $sourceToUuid = [];

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.employees', ['EmployeeID' => $row->employee_id]);
            $sourceToUuid[$this->sourceInt($row->employee_id)] = $uuid;

            StagingEmployee::create([
                'id' => $uuid,
                'last_name' => $row->last_name ?? '',
                'first_name' => $row->first_name ?? '',
                'title' => $row->title,
                'reports_to' => null,
            ]);
            $count++;
        }

        foreach ($rows as $row) {
            if ($row->reports_to !== null && isset($sourceToUuid[$this->sourceInt($row->reports_to)])) {
                StagingEmployee::where('id', $sourceToUuid[$this->sourceInt($row->employee_id)])
                    ->update(['reports_to' => $sourceToUuid[$this->sourceInt($row->reports_to)]]);
            }
        }

        return $count;
    }
}

class CustomerMapper extends TableMapper
{
    public function __construct(private SourceIdentityRegistry $registry) {}

    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.customers")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.customers', ['CustomerID' => $row->customer_id]);
            StagingCustomer::create([
                'id' => $uuid,
                'company_name' => $row->company_name ?? '',
                'contact_name' => $row->contact_name,
                'contact_title' => $row->contact_title,
                'address' => $row->address,
                'city' => $row->city,
                'region' => $row->region,
                'postal_code' => $row->postal_code,
                'country' => $row->country,
                'phone' => $row->phone,
                'fax' => $row->fax,
            ]);
            $count++;
        }

        return $count;
    }
}

class ProductMapper_ extends TableMapper
{
    public function __construct(private SourceIdentityRegistry $registry) {}

    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.products")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.products', ['ProductID' => $row->product_id]);
            $supplierUuid = $row->supplier_id !== null
                ? $this->registry->getOrMint('northwind.suppliers', ['SupplierID' => $row->supplier_id])
                : null;
            $categoryUuid = $row->category_id !== null
                ? $this->registry->getOrMint('northwind.categories', ['CategoryID' => $row->category_id])
                : null;

            StagingProduct::create([
                'id' => $uuid,
                'product_name' => $row->product_name ?? '',
                'supplier_id' => $supplierUuid,
                'category_id' => $categoryUuid,
                'quantity_per_unit' => $row->quantity_per_unit,
                'unit_price' => $this->sourceFloat($row->unit_price ?? 0),
                'units_in_stock' => $this->sourceInt($row->units_in_stock ?? 0),
                'units_on_order' => $this->sourceInt($row->units_on_order ?? 0),
                'reorder_level' => $this->sourceInt($row->reorder_level ?? 0),
                'discontinued' => (bool) ($row->discontinued ?? 0),
            ]);
            $count++;
        }

        return $count;
    }
}
