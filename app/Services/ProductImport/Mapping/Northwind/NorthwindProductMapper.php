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
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): array
    {
        $registry = app(SourceIdentityRegistry::class);
        $context = app(StagingContext::class);

        $mappers = [
            new CategoryMapper($registry),
            new CustomerMapper($registry),
            new EmployeeMapper($registry),
            new RegionMapper($registry),
            new TerritoryMapper($registry),
            new EmployeeTerritoryMapper($registry),
            new ShipperMapper($registry),
            new SupplierMapper($registry),
            new ProductMapper_($registry),
            new OrderMapper($registry),
            new OrderDetailMapper($registry),
        ];

        $totalRows = 0;

        $context->run(function () use ($mappers, $sourceSchema, $stagingSchema, &$totalRows) {
            foreach ($mappers as $mapper) {
                $totalRows += $mapper->load($sourceSchema, $stagingSchema);
            }
        });

        return ['tables' => count($mappers), 'rows' => $totalRows];
    }

    #[\Override]
    protected function mappers(): array
    {
        $registry = app(SourceIdentityRegistry::class);

        return [
            new CategoryMapper($registry),
            new CustomerMapper($registry),
            new EmployeeMapper($registry),
            new RegionMapper($registry),
            new TerritoryMapper($registry),
            new EmployeeTerritoryMapper($registry),
            new ShipperMapper($registry),
            new SupplierMapper($registry),
            new ProductMapper_($registry),
            new OrderMapper($registry),
            new OrderDetailMapper($registry),
        ];
    }
}

class CategoryMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.categories")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('northwind.categories', ['CategoryID' => $row->category_id]);
            StagingCategory::create([
                'id' => $uuid,
                'category_name' => $row->category_name ?? '',
                'description' => $row->description,
                'picture' => $row->picture,
            ]);
            $count++;
        }

        return $count;
    }
}

class SupplierMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
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
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
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
                'title_of_courtesy' => $row->title_of_courtesy,
                'birth_date' => $row->birth_date,
                'hire_date' => $row->hire_date,
                'address' => $row->address,
                'city' => $row->city,
                'region' => $row->region,
                'postal_code' => $row->postal_code,
                'country' => $row->country,
                'home_phone' => $row->home_phone,
                'extension' => $row->extension,
                'photo' => $row->photo,
                'notes' => $row->notes,
                'photo_path' => $row->photo_path,
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
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
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
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
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

abstract class NorthwindRawTableMapper extends TableMapper
{
    public function __construct(
        protected SourceIdentityRegistry $registry,
    ) {}

    /**
     * Insert one mapped row with staging timestamps.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function insert(string $stagingSchema, string $table, array $attributes): void
    {
        $timestamp = now();

        DB::table("{$stagingSchema}.{$table}")->insert(
            array_merge($attributes, [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]),
        );
    }

    protected function sourceTableExists(string $sourceSchema, string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $sourceSchema)
            ->where('table_name', $table)
            ->exists();
    }
}

class RegionMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'region')) {
            return 0;
        }

        $count = 0;

        foreach (DB::table("{$sourceSchema}.region")->get() as $row) {
            $this->insert($stagingSchema, 'regions', [
                'id' => $this->registry->getOrMint('northwind.regions', ['RegionID' => $row->region_id]),
                'region_description' => $row->region_description ?? '',
            ]);
            $count++;
        }

        return $count;
    }
}

class TerritoryMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'territories')) {
            return 0;
        }

        foreach (DB::table("{$sourceSchema}.territories")->get() as $row) {
            $this->insert($stagingSchema, 'territories', [
                'id' => $this->registry->getOrMint('northwind.territories', ['TerritoryID' => $row->territory_id]),
                'territory_description' => $row->territory_description ?? '',
                'region_id' => $this->registry->getOrMint('northwind.regions', ['RegionID' => $row->region_id]),
            ]);
        }

        return $this->countSourceRows($sourceSchema, 'territories');
    }
}

class EmployeeTerritoryMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'employee_territories')) {
            return 0;
        }

        foreach (DB::table("{$sourceSchema}.employee_territories")->get() as $row) {
            $this->insert($stagingSchema, 'employee_territories', [
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'employee_id' => $this->registry->getOrMint('northwind.employees', ['EmployeeID' => $row->employee_id]),
                'territory_id' => $this->registry->getOrMint('northwind.territories', [
                    'TerritoryID' => $row->territory_id,
                ]),
            ]);
        }

        return $this->countSourceRows($sourceSchema, 'employee_territories');
    }
}

class ShipperMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'shippers')) {
            return 0;
        }

        foreach (DB::table("{$sourceSchema}.shippers")->get() as $row) {
            $this->insert($stagingSchema, 'shippers', [
                'id' => $this->registry->getOrMint('northwind.shippers', ['ShipperID' => $row->shipper_id]),
                'company_name' => $row->company_name ?? '',
                'phone' => $row->phone,
            ]);
        }

        return $this->countSourceRows($sourceSchema, 'shippers');
    }
}

class OrderMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'orders')) {
            return 0;
        }

        foreach (DB::table("{$sourceSchema}.orders")->get() as $row) {
            $customerId = null;
            if ($row->customer_id !== null) {
                $customerId = $this->registry->getOrMint('northwind.customers', ['CustomerID' => $row->customer_id]);
            }

            $employeeId = null;
            if ($row->employee_id !== null) {
                $employeeId = $this->registry->getOrMint('northwind.employees', ['EmployeeID' => $row->employee_id]);
            }

            $shipperId = null;
            if ($row->ship_via !== null) {
                $shipperId = $this->registry->getOrMint('northwind.shippers', ['ShipperID' => $row->ship_via]);
            }

            $this->insert($stagingSchema, 'orders', [
                'id' => $this->registry->getOrMint('northwind.orders', ['OrderID' => $row->order_id]),
                'customer_id' => $customerId,
                'employee_id' => $employeeId,
                'order_date' => $row->order_date,
                'required_date' => $row->required_date,
                'shipped_date' => $row->shipped_date,
                'ship_via' => $shipperId,
                'freight' => $this->sourceFloat($row->freight ?? 0),
                'ship_name' => $row->ship_name,
                'ship_address' => $row->ship_address,
                'ship_city' => $row->ship_city,
                'ship_region' => $row->ship_region,
                'ship_postal_code' => $row->ship_postal_code,
                'ship_country' => $row->ship_country,
            ]);
        }

        return $this->countSourceRows($sourceSchema, 'orders');
    }
}

class OrderDetailMapper extends NorthwindRawTableMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        if (! $this->sourceTableExists($sourceSchema, 'order_details')) {
            return 0;
        }

        foreach (DB::table("{$sourceSchema}.order_details")->get() as $row) {
            $this->insert($stagingSchema, 'order_details', [
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'order_id' => $this->registry->getOrMint('northwind.orders', ['OrderID' => $row->order_id]),
                'product_id' => $this->registry->getOrMint('northwind.products', ['ProductID' => $row->product_id]),
                'unit_price' => $this->sourceFloat($row->unit_price ?? 0),
                'quantity' => $this->sourceInt($row->quantity ?? 0),
                'discount' => $this->sourceFloat($row->discount ?? 0),
            ]);
        }

        return $this->countSourceRows($sourceSchema, 'order_details');
    }
}
