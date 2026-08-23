<?php

declare(strict_types=1);

use App\Filament\Northwind\Resources\CategoryResource;
use App\Filament\Northwind\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Northwind\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Northwind\Resources\CustomerResource;
use App\Filament\Northwind\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Northwind\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Northwind\Resources\EmployeeResource;
use App\Filament\Northwind\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Northwind\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Northwind\Resources\OrderResource;
use App\Filament\Northwind\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Northwind\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Northwind\Resources\ProductResource;
use App\Filament\Northwind\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Northwind\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Northwind\Resources\ShipperResource;
use App\Filament\Northwind\Resources\ShipperResource\Pages\EditShipper;
use App\Filament\Northwind\Resources\ShipperResource\Pages\ListShippers;
use App\Filament\Northwind\Resources\SupplierResource;
use App\Filament\Northwind\Resources\SupplierResource\Pages\EditSupplier;
use App\Filament\Northwind\Resources\SupplierResource\Pages\ListSuppliers;
use App\Models\Northwind\Category;
use App\Models\Northwind\Customer;
use App\Models\Northwind\Employee;
use App\Models\Northwind\Order;
use App\Models\Northwind\Product;
use App\Models\Northwind\Shipper;
use App\Models\Northwind\Supplier;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

covers(
    CategoryResource::class,
    ListCategories::class,
    EditCategory::class,
    CustomerResource::class,
    ListCustomers::class,
    EditCustomer::class,
    EmployeeResource::class,
    ListEmployees::class,
    EditEmployee::class,
    OrderResource::class,
    ListOrders::class,
    EditOrder::class,
    ProductResource::class,
    ListProducts::class,
    EditProduct::class,
    ShipperResource::class,
    ListShippers::class,
    EditShipper::class,
    SupplierResource::class,
    ListSuppliers::class,
    EditSupplier::class,
);

beforeEach(function () {
    /** @var User $curator */
    $this->curator = User::factory()->create();
    $this->curator->assignRole(Role::findOrCreate('northwind_curator', 'web'));
});

test('northwind curator can access all northwind resource list pages', function () {
    $endpoints = [
        '/northwind/categories',
        '/northwind/customers',
        '/northwind/employees',
        '/northwind/orders',
        '/northwind/products',
        '/northwind/shippers',
        '/northwind/suppliers',
    ];

    foreach ($endpoints as $endpoint) {
        $this->actingAs($this->curator)
            ->get($endpoint)
            ->assertSuccessful();
    }
});

test('category resource renders columns and data', function () {
    $category = Category::create(['category_name' => 'Beverages']);

    $this->actingAs($this->curator)
        ->get('/northwind/categories')
        ->assertSuccessful()
        ->assertSee('Beverages');

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$category])
        ->assertTableColumnExists('category_name');
});

test('customer resource renders columns and data', function () {
    $customer = Customer::create([
        'company_name' => 'Alfreds Futterkiste',
        'contact_name' => 'Maria Anders',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    $this->actingAs($this->curator)
        ->get('/northwind/customers')
        ->assertSuccessful()
        ->assertSee('Alfreds Futterkiste');

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertTableColumnExists('company_name')
        ->assertTableColumnExists('contact_name')
        ->assertTableColumnExists('city')
        ->assertTableColumnExists('country');
});

test('employee resource renders columns and data', function () {
    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Davolio',
        'title' => 'Sales Representative',
        'city' => 'Seattle',
        'country' => 'USA',
    ]);

    $this->actingAs($this->curator)
        ->get('/northwind/employees')
        ->assertSuccessful()
        ->assertSee('Davolio');

    Livewire::test(ListEmployees::class)
        ->assertCanSeeTableRecords([$employee])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name')
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('city');
});

test('order resource renders columns and data', function () {
    $customer = Customer::create(['company_name' => 'Alfreds Futterkiste']);
    $employee = Employee::create(['first_name' => 'Nancy', 'last_name' => 'Davolio']);
    $order = Order::create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'order_date' => '2026-01-01 00:00:00',
        'freight' => 32.38,
        'ship_country' => 'Germany',
    ]);

    $this->actingAs($this->curator)
        ->get('/northwind/orders')
        ->assertSuccessful()
        ->assertSee('Germany');

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertTableColumnExists('customer.company_name')
        ->assertTableColumnExists('employee.last_name')
        ->assertTableColumnExists('order_date')
        ->assertTableColumnExists('freight')
        ->assertTableColumnExists('ship_country');
});

test('product resource renders columns and data', function () {
    $supplier = Supplier::create(['company_name' => 'Exotic Liquids']);
    $category = Category::create(['category_name' => 'Beverages']);
    $product = Product::create([
        'product_name' => 'Chai',
        'supplier_id' => $supplier->id,
        'category_id' => $category->id,
        'unit_price' => 18.00,
        'units_in_stock' => 39,
        'units_on_order' => 0,
        'reorder_level' => 10,
        'discontinued' => false,
    ]);

    $this->actingAs($this->curator)
        ->get('/northwind/products')
        ->assertSuccessful()
        ->assertSee('Chai');

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$product])
        ->assertTableColumnExists('product_name')
        ->assertTableColumnExists('supplier.company_name')
        ->assertTableColumnExists('category.category_name')
        ->assertTableColumnExists('unit_price')
        ->assertTableColumnExists('discontinued');
});

test('shipper resource renders columns and data', function () {
    $shipper = Shipper::create(['company_name' => 'Speedy Express']);

    $this->actingAs($this->curator)
        ->get('/northwind/shippers')
        ->assertSuccessful()
        ->assertSee('Speedy Express');

    Livewire::test(ListShippers::class)
        ->assertCanSeeTableRecords([$shipper])
        ->assertTableColumnExists('company_name');
});

test('supplier resource renders columns and data', function () {
    $supplier = Supplier::create([
        'company_name' => 'Exotic Liquids',
        'contact_name' => 'Charlotte Cooper',
        'city' => 'London',
        'country' => 'UK',
    ]);

    $this->actingAs($this->curator)
        ->get('/northwind/suppliers')
        ->assertSuccessful()
        ->assertSee('Exotic Liquids');

    Livewire::test(ListSuppliers::class)
        ->assertCanSeeTableRecords([$supplier])
        ->assertTableColumnExists('company_name')
        ->assertTableColumnExists('contact_name')
        ->assertTableColumnExists('city')
        ->assertTableColumnExists('country');
});

test('category edit page renders form', function () {
    $category = Category::create(['category_name' => 'Beverages']);

    $this->actingAs($this->curator)
        ->get("/northwind/categories/{$category->id}/edit")
        ->assertSuccessful();
});

test('customer edit page renders form', function () {
    $customer = Customer::create([
        'company_name' => 'Alfreds Futterkiste',
        'contact_name' => 'Maria Anders',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    $this->actingAs($this->curator)
        ->get("/northwind/customers/{$customer->id}/edit")
        ->assertSuccessful();
});

test('employee edit page renders form', function () {
    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Davolio',
        'title' => 'Sales Representative',
        'city' => 'Seattle',
        'country' => 'USA',
    ]);

    $this->actingAs($this->curator)
        ->get("/northwind/employees/{$employee->id}/edit")
        ->assertSuccessful();
});

test('order edit page renders form', function () {
    $customer = Customer::create(['company_name' => 'Alfreds Futterkiste']);
    $employee = Employee::create(['first_name' => 'Nancy', 'last_name' => 'Davolio']);
    $order = Order::create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'order_date' => '2026-01-01 00:00:00',
        'freight' => 32.38,
        'ship_country' => 'Germany',
    ]);

    $this->actingAs($this->curator)
        ->get("/northwind/orders/{$order->id}/edit")
        ->assertSuccessful();
});

test('product edit page renders form', function () {
    $supplier = Supplier::create(['company_name' => 'Exotic Liquids']);
    $category = Category::create(['category_name' => 'Beverages']);
    $product = Product::create([
        'product_name' => 'Chai',
        'supplier_id' => $supplier->id,
        'category_id' => $category->id,
        'unit_price' => 18.00,
        'units_in_stock' => 39,
        'units_on_order' => 0,
        'reorder_level' => 10,
        'discontinued' => false,
    ]);

    $this->actingAs($this->curator)
        ->get("/northwind/products/{$product->id}/edit")
        ->assertSuccessful();
});

test('shipper edit page renders form', function () {
    $shipper = Shipper::create(['company_name' => 'Speedy Express']);

    $this->actingAs($this->curator)
        ->get("/northwind/shippers/{$shipper->id}/edit")
        ->assertSuccessful();
});

test('supplier edit page renders form', function () {
    $supplier = Supplier::create([
        'company_name' => 'Exotic Liquids',
        'contact_name' => 'Charlotte Cooper',
        'city' => 'London',
        'country' => 'UK',
    ]);

    $this->actingAs($this->curator)
        ->get("/northwind/suppliers/{$supplier->id}/edit")
        ->assertSuccessful();
});
