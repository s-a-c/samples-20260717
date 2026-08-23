<?php

declare(strict_types=1);

use App\Filament\Pagila\Resources\ActorResource;
use App\Filament\Pagila\Resources\ActorResource\Pages\EditActor;
use App\Filament\Pagila\Resources\ActorResource\Pages\ListActors;
use App\Filament\Pagila\Resources\CategoryResource;
use App\Filament\Pagila\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Pagila\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Pagila\Resources\CustomerResource;
use App\Filament\Pagila\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Pagila\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Pagila\Resources\FilmResource;
use App\Filament\Pagila\Resources\FilmResource\Pages\EditFilm;
use App\Filament\Pagila\Resources\FilmResource\Pages\ListFilms;
use App\Filament\Pagila\Resources\InventoryResource;
use App\Filament\Pagila\Resources\InventoryResource\Pages\EditInventory;
use App\Filament\Pagila\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Pagila\Resources\LanguageResource;
use App\Filament\Pagila\Resources\LanguageResource\Pages\EditLanguage;
use App\Filament\Pagila\Resources\LanguageResource\Pages\ListLanguages;
use App\Filament\Pagila\Resources\PaymentResource;
use App\Filament\Pagila\Resources\PaymentResource\Pages\EditPayment;
use App\Filament\Pagila\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Pagila\Resources\RentalResource;
use App\Filament\Pagila\Resources\RentalResource\Pages\EditRental;
use App\Filament\Pagila\Resources\RentalResource\Pages\ListRentals;
use App\Filament\Pagila\Resources\StaffResource;
use App\Filament\Pagila\Resources\StaffResource\Pages\EditStaff;
use App\Filament\Pagila\Resources\StaffResource\Pages\ListStaff;
use App\Filament\Pagila\Resources\StoreResource;
use App\Filament\Pagila\Resources\StoreResource\Pages\EditStore;
use App\Filament\Pagila\Resources\StoreResource\Pages\ListStores;
use App\Models\Pagila\Actor;
use App\Models\Pagila\Category;
use App\Models\Pagila\Customer;
use App\Models\Pagila\Film;
use App\Models\Pagila\Inventory;
use App\Models\Pagila\Language;
use App\Models\Pagila\Payment;
use App\Models\Pagila\Rental;
use App\Models\Pagila\Staff;
use App\Models\Pagila\Store;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

covers(
    ActorResource::class,
    ListActors::class,
    EditActor::class,
    CategoryResource::class,
    ListCategories::class,
    EditCategory::class,
    CustomerResource::class,
    ListCustomers::class,
    EditCustomer::class,
    FilmResource::class,
    ListFilms::class,
    EditFilm::class,
    InventoryResource::class,
    ListInventories::class,
    EditInventory::class,
    LanguageResource::class,
    ListLanguages::class,
    EditLanguage::class,
    PaymentResource::class,
    ListPayments::class,
    EditPayment::class,
    RentalResource::class,
    ListRentals::class,
    EditRental::class,
    StaffResource::class,
    ListStaff::class,
    EditStaff::class,
    StoreResource::class,
    ListStores::class,
    EditStore::class,
);

beforeEach(function () {
    $this->pagilaCurator = User::factory()->create();
    $this->pagilaCurator->assignRole(Role::findOrCreate('pagila_curator', 'web'));

    $this->northwindCurator = User::factory()->create();
    $this->northwindCurator->assignRole(Role::findOrCreate('northwind_curator', 'web'));
});

test('pagila curator can access all pagila resource list pages', function () {
    $endpoints = [
        '/pagila/actors',
        '/pagila/films',
        '/pagila/categories',
        '/pagila/customers',
        '/pagila/staff',
        '/pagila/stores',
        '/pagila/rentals',
        '/pagila/payments',
        '/pagila/inventories',
        '/pagila/languages',
    ];

    foreach ($endpoints as $endpoint) {
        $this->actingAs($this->pagilaCurator)
            ->get($endpoint)
            ->assertSuccessful();
    }
});

test('northwind curator is forbidden from pagila pages', function () {
    $endpoints = [
        '/pagila/actors',
        '/pagila/films',
        '/pagila/categories',
        '/pagila/customers',
        '/pagila/staff',
        '/pagila/stores',
        '/pagila/rentals',
        '/pagila/payments',
        '/pagila/inventories',
        '/pagila/languages',
    ];

    foreach ($endpoints as $endpoint) {
        $this->actingAs($this->northwindCurator)
            ->get($endpoint)
            ->assertForbidden();
    }
});

test('actor resource renders columns and data', function () {
    $actor = Actor::create([
        'first_name' => 'PENELOPE',
        'last_name' => 'GUINESS',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/actors')
        ->assertSuccessful()
        ->assertSee('PENELOPE')
        ->assertSee('GUINESS');

    Livewire::test(ListActors::class)
        ->assertCanSeeTableRecords([$actor])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name');
});

test('film resource renders columns and data', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => $language->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
        'rating' => 'PG',
        'length' => 86,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/films')
        ->assertSuccessful()
        ->assertSee('ACADEMY DINOSAUR');

    Livewire::test(ListFilms::class)
        ->assertCanSeeTableRecords([$film])
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('language.name')
        ->assertTableColumnExists('release_year')
        ->assertTableColumnExists('rating');
});

test('category resource renders columns and data', function () {
    $category = Category::create(['name' => 'Action']);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/categories')
        ->assertSuccessful()
        ->assertSee('Action');

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$category])
        ->assertTableColumnExists('name');
});

test('customer resource renders columns and data', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'email' => 'mary.smith@pagilacustomer.org',
        'active' => true,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/customers')
        ->assertSuccessful()
        ->assertSee('MARY')
        ->assertSee('SMITH');

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('active');
});

test('staff resource renders columns and data', function () {
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'email' => 'Mike.Hillyer@pagilastaff.com',
        'active' => true,
        'username' => 'Mike',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/staff')
        ->assertSuccessful()
        ->assertSee('Hillyer');

    Livewire::test(ListStaff::class)
        ->assertCanSeeTableRecords([$staff])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('username');
});

test('store resource renders columns and data', function () {
    $store = Store::create(['address_id' => App\Models\Pagila\Address::create([
        'address' => '47 MyPagila Drive',
        'city_id' => App\Models\Pagila\City::create(['city' => 'Test City', 'country_id' => App\Models\Pagila\Country::create(['country' => 'Test Country'])->id])->id,
    ])->id]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/stores')
        ->assertSuccessful()
        ->assertSee('47 MyPagila Drive');

    Livewire::test(ListStores::class)
        ->assertCanSeeTableRecords([$store])
        ->assertTableColumnExists('id')
        ->assertTableColumnExists('address.address');
});

test('rental resource renders columns and data', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'active' => true,
    ]);
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'active' => true,
    ]);
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => Language::create(['name' => 'English'])->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
    ]);
    $inventory = Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);
    $rental = Rental::create([
        'rental_date' => '2026-01-01 12:00:00',
        'inventory_id' => $inventory->id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/rentals')
        ->assertSuccessful()
        ->assertSee('SMITH');

    Livewire::test(ListRentals::class)
        ->assertCanSeeTableRecords([$rental])
        ->assertTableColumnExists('rental_date')
        ->assertTableColumnExists('customer.last_name')
        ->assertTableColumnExists('staff.last_name');
});

test('payment resource renders columns and data', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'active' => true,
    ]);
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'active' => true,
    ]);
    $payment = Payment::create([
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
        'amount' => 5.99,
        'payment_date' => '2026-01-01 12:00:00',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/payments')
        ->assertSuccessful()
        ->assertSee('Hillyer');

    Livewire::test(ListPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertTableColumnExists('customer.last_name')
        ->assertTableColumnExists('staff.last_name')
        ->assertTableColumnExists('amount');
});

test('inventory resource renders columns and data', function () {
    $store = Store::create();
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => Language::create(['name' => 'English'])->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
    ]);
    $inventory = Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/inventories')
        ->assertSuccessful()
        ->assertSee('ACADEMY DINOSAUR');

    Livewire::test(ListInventories::class)
        ->assertCanSeeTableRecords([$inventory])
        ->assertTableColumnExists('film.title')
        ->assertTableColumnExists('store.id');
});

test('language resource renders columns and data', function () {
    $language = Language::create(['name' => 'English']);

    $this->actingAs($this->pagilaCurator)
        ->get('/pagila/languages')
        ->assertSuccessful()
        ->assertSee('English');

    Livewire::test(ListLanguages::class)
        ->assertCanSeeTableRecords([$language])
        ->assertTableColumnExists('name');
});

test('actor edit page renders form', function () {
    $actor = Actor::create([
        'first_name' => 'PENELOPE',
        'last_name' => 'GUINESS',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/actors/{$actor->id}/edit")
        ->assertSuccessful();
});

test('film edit page renders form', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => $language->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
        'rating' => 'PG',
        'length' => 86,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/films/{$film->id}/edit")
        ->assertSuccessful();
});

test('category edit page renders form', function () {
    $category = Category::create(['name' => 'Action']);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/categories/{$category->id}/edit")
        ->assertSuccessful();
});

test('customer edit page renders form', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'email' => 'mary.smith@pagilacustomer.org',
        'active' => true,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/customers/{$customer->id}/edit")
        ->assertSuccessful();
});

test('staff edit page renders form', function () {
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'email' => 'Mike.Hillyer@pagilastaff.com',
        'active' => true,
        'username' => 'Mike',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/staff/{$staff->id}/edit")
        ->assertSuccessful();
});

test('store edit page renders form', function () {
    $store = Store::create(['address_id' => App\Models\Pagila\Address::create([
        'address' => '47 MyPagila Drive',
        'city_id' => App\Models\Pagila\City::create(['city' => 'Test City', 'country_id' => App\Models\Pagila\Country::create(['country' => 'Test Country'])->id])->id,
    ])->id]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/stores/{$store->id}/edit")
        ->assertSuccessful();
});

test('rental edit page renders form', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'active' => true,
    ]);
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'active' => true,
    ]);
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => Language::create(['name' => 'English'])->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
    ]);
    $inventory = Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);
    $rental = Rental::create([
        'rental_date' => '2026-01-01 12:00:00',
        'inventory_id' => $inventory->id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/rentals/{$rental->id}/edit")
        ->assertSuccessful();
});

test('payment edit page renders form', function () {
    $store = Store::create();
    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'active' => true,
    ]);
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'active' => true,
    ]);
    $payment = Payment::create([
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
        'amount' => 5.99,
        'payment_date' => '2026-01-01 12:00:00',
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/payments/{$payment->id}/edit")
        ->assertSuccessful();
});

test('inventory edit page renders form', function () {
    $store = Store::create();
    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => Language::create(['name' => 'English'])->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
    ]);
    $inventory = Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/inventories/{$inventory->id}/edit")
        ->assertSuccessful();
});

test('language edit page renders form', function () {
    $language = Language::create(['name' => 'English']);

    $this->actingAs($this->pagilaCurator)
        ->get("/pagila/languages/{$language->id}/edit")
        ->assertSuccessful();
});
