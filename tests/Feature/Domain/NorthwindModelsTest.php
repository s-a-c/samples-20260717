<?php

declare(strict_types=1);

use App\Enums\SamplesProduct;
use App\Models\Northwind\Category;
use App\Models\Northwind\Customer;
use App\Models\Northwind\Employee;
use App\Models\Northwind\EmployeeTerritory;
use App\Models\Northwind\Order;
use App\Models\Northwind\OrderDetail;
use App\Models\Northwind\Product;
use App\Models\Northwind\Region;
use App\Models\Northwind\Shipper;
use App\Models\Northwind\Supplier;
use App\Models\Northwind\Territory;

covers(
    Category::class,
    Customer::class,
    Employee::class,
    EmployeeTerritory::class,
    Order::class,
    OrderDetail::class,
    Region::class,
    Shipper::class,
    Supplier::class,
    Territory::class,
);

test('northwind models report their product domain', function () {
    expect((new Category)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Customer)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Employee)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new EmployeeTerritory)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Order)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new OrderDetail)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Region)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Shipper)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Supplier)->getProductDomain())->toBe(SamplesProduct::Northwind)
        ->and((new Territory)->getProductDomain())->toBe(SamplesProduct::Northwind);
});

test('northwind category and supplier product relations resolve', function () {
    $category = Category::create(['category_name' => 'Beverages', 'description' => 'Drinks']);
    $supplier = Supplier::create(['company_name' => 'Exotic Liquids', 'contact_name' => 'Charlotte Cooper']);

    Product::create([
        'product_name' => 'Chai',
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'unit_price' => 18.00,
    ]);

    expect($category->products->first()->product_name)->toBe('Chai')
        ->and($supplier->products->first()->product_name)->toBe('Chai');
});

test('northwind employee self-referential, order, and territory relations resolve', function () {
    $manager = Employee::create(['first_name' => 'Andrew', 'last_name' => 'Fuller', 'title' => 'VP Sales']);

    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Davolio',
        'title' => 'Sales Rep',
        'reports_to' => $manager->id,
    ]);

    $region = Region::create(['region_description' => 'Eastern']);
    $territory = Territory::create(['territory_description' => 'New York', 'region_id' => $region->id]);
    $employee->territories()->attach($territory->id);

    $customer = Customer::create(['company_name' => 'Test Co']);
    Order::create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'order_date' => now(),
        'freight' => 10.00,
    ]);

    expect($employee->manager->first_name)->toBe('Andrew')
        ->and($manager->subordinates->first()->first_name)->toBe('Nancy')
        ->and($employee->orders->first()->customer_id)->toBe($customer->id)
        ->and($employee->territories->first()->territory_description)->toBe('New York');
});

test('northwind region and territory relations resolve', function () {
    $region = Region::create(['region_description' => 'Western']);
    $territory = Territory::create(['territory_description' => 'Seattle', 'region_id' => $region->id]);

    $employee = Employee::create(['first_name' => 'Laura', 'last_name' => 'Callahan']);
    $territory->employees()->attach($employee->id);

    expect($region->territories->first()->territory_description)->toBe('Seattle')
        ->and($territory->region->region_description)->toBe('Western')
        ->and($territory->employees->first()->first_name)->toBe('Laura');
});

test('northwind employee territory pivot relations resolve', function () {
    $employee = Employee::create(['first_name' => 'Robert', 'last_name' => 'King']);
    $region = Region::create(['region_description' => 'Northern']);
    $territory = Territory::create(['territory_description' => 'Boston', 'region_id' => $region->id]);

    $employee->territories()->attach($territory->id);

    $pivot = EmployeeTerritory::where('employee_id', $employee->id)
        ->where('territory_id', $territory->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->employee->id)->toBe($employee->id);
    expect($pivot->territory->id)->toBe($territory->id);
});

test('northwind customer, shipper, order and order detail relations resolve', function () {
    $customer = Customer::create(['company_name' => 'Alfreds Futterkiste', 'contact_name' => 'Maria Anders']);
    $employee = Employee::create(['first_name' => 'Janet', 'last_name' => 'Leverling']);
    $shipper = Shipper::create(['company_name' => 'Speedy Express', 'phone' => '555-9831']);
    $category = Category::create(['category_name' => 'Confections']);
    $product = Product::create([
        'product_name' => 'Chocolade',
        'category_id' => $category->id,
        'unit_price' => 12.75,
    ]);

    $order = Order::create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'ship_via' => $shipper->id,
        'order_date' => now(),
        'freight' => 32.38,
        'ship_name' => 'Alfreds Futterkiste',
    ]);

    $orderDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'unit_price' => 12.75,
        'quantity' => 10,
        'discount' => 0.00,
    ]);

    expect($customer->orders->first()->id)->toBe($order->id)
        ->and($shipper->orders->first()->id)->toBe($order->id)
        ->and($order->customer->company_name)->toBe('Alfreds Futterkiste')
        ->and($order->employee->first_name)->toBe('Janet')
        ->and($order->shipper->company_name)->toBe('Speedy Express')
        ->and($order->orderDetails->first()->id)->toBe($orderDetail->id)
        ->and($order->products->first()->product_name)->toBe('Chocolade');
    expect($orderDetail->order->id)->toBe($order->id);
    expect($orderDetail->product->product_name)->toBe('Chocolade');
});
