<?php

use App\Domain\Northwind\Models\Category;
use App\Domain\Northwind\Models\Customer;
use App\Domain\Northwind\Models\Employee;
use App\Domain\Northwind\Models\EmployeeTerritory;
use App\Domain\Northwind\Models\Order;
use App\Domain\Northwind\Models\OrderDetail;
use App\Domain\Northwind\Models\Product;
use App\Domain\Northwind\Models\Region;
use App\Domain\Northwind\Models\Shipper;
use App\Domain\Northwind\Models\Supplier;
use App\Domain\Northwind\Models\Territory;

test('northwind category and product models can be persisted and queried', function () {
    $category = Category::create([
        'category_name' => 'Beverages',
        'description' => 'Soft drinks, coffees, teas, beers, and ales',
    ]);

    $supplier = Supplier::create([
        'company_name' => 'Exotic Liquids',
        'contact_name' => 'Charlotte Cooper',
    ]);

    $product = Product::create([
        'product_name' => 'Chai',
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'quantity_per_unit' => '10 boxes x 20 bags',
        'unit_price' => 18.00,
        'units_in_stock' => 39,
        'discontinued' => false,
    ]);

    expect($product->id)->not->toBeNull();
    expect($product->category->category_name)->toBe('Beverages');
    expect($product->supplier->company_name)->toBe('Exotic Liquids');
    expect($category->products->first()->product_name)->toBe('Chai');
    expect($supplier->products->first()->product_name)->toBe('Chai');
});

test('northwind employee, region, territory, and employee_territory relationships work', function () {
    $manager = Employee::create([
        'first_name' => 'Andrew',
        'last_name' => 'Fuller',
        'title' => 'Vice President, Sales',
    ]);

    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Davolio',
        'title' => 'Sales Representative',
        'reports_to' => $manager->id,
    ]);

    $region = Region::create([
        'region_description' => 'Eastern',
    ]);

    $territory = Territory::create([
        'territory_description' => 'New York',
        'region_id' => $region->id,
    ]);

    $employee->territories()->attach($territory->id);

    expect($employee->manager->first_name)->toBe('Andrew');
    expect($manager->subordinates->first()->first_name)->toBe('Nancy');
    expect($territory->region->region_description)->toBe('Eastern');
    expect($region->territories->first()->territory_description)->toBe('New York');
    expect($employee->territories->first()->territory_description)->toBe('New York');
    expect($territory->employees->first()->first_name)->toBe('Nancy');

    $pivot = EmployeeTerritory::where('employee_id', $employee->id)->where('territory_id', $territory->id)->first();
    expect($pivot)->not->toBeNull();
    expect($pivot->employee->id)->toBe($employee->id);
    expect($pivot->territory->id)->toBe($territory->id);
});

test('northwind customer, shipper, order, and order_detail relationships work', function () {
    $customer = Customer::create([
        'company_name' => 'Alfreds Futterkiste',
        'contact_name' => 'Maria Anders',
    ]);

    $employee = Employee::create([
        'first_name' => 'Janet',
        'last_name' => 'Leverling',
    ]);

    $shipper = Shipper::create([
        'company_name' => 'Speedy Express',
        'phone' => '(503) 555-9831',
    ]);

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

    expect($order->customer->company_name)->toBe('Alfreds Futterkiste');
    expect($order->employee->first_name)->toBe('Janet');
    expect($order->shipper->company_name)->toBe('Speedy Express');
    expect($customer->orders->first()->ship_name)->toBe('Alfreds Futterkiste');
    expect($shipper->orders->first()->id)->toBe($order->id);
    expect($order->orderDetails->first()->product->product_name)->toBe('Chocolade');
    expect($orderDetail->order->id)->toBe($order->id);
    expect($orderDetail->product->id)->toBe($product->id);
    expect($order->products->first()->product_name)->toBe('Chocolade');
});
