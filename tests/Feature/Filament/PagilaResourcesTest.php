<?php

namespace Tests\Feature\Filament;

use App\Domain\Pagila\Models\Actor;
use App\Domain\Pagila\Models\Category;
use App\Domain\Pagila\Models\Customer;
use App\Domain\Pagila\Models\Film;
use App\Domain\Pagila\Models\Inventory;
use App\Domain\Pagila\Models\Language;
use App\Domain\Pagila\Models\Payment;
use App\Domain\Pagila\Models\Rental;
use App\Domain\Pagila\Models\Staff;
use App\Domain\Pagila\Models\Store;
use App\Filament\Pagila\Resources\ActorResource\Pages\ListActors;
use App\Filament\Pagila\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Pagila\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Pagila\Resources\FilmResource\Pages\ListFilms;
use App\Filament\Pagila\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Pagila\Resources\LanguageResource\Pages\ListLanguages;
use App\Filament\Pagila\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Pagila\Resources\RentalResource\Pages\ListRentals;
use App\Filament\Pagila\Resources\StaffResource\Pages\ListStaff;
use App\Filament\Pagila\Resources\StoreResource\Pages\ListStores;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PagilaResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $pagilaCurator;

    private User $northwindCurator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pagilaCurator = User::factory()->create();
        $this->pagilaCurator->assignRole(Role::findOrCreate('pagila_curator', 'web'));

        $this->northwindCurator = User::factory()->create();
        $this->northwindCurator->assignRole(Role::findOrCreate('northwind_curator', 'web'));
    }

    public function test_pagila_curator_can_access_all_pagila_resource_list_pages(): void
    {
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
    }

    public function test_northwind_curator_is_forbidden_from_pagila_pages(): void
    {
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
    }

    public function test_actor_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_film_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_category_resource_renders_columns_and_data(): void
    {
        $category = Category::create(['name' => 'Action']);

        $this->actingAs($this->pagilaCurator)
            ->get('/pagila/categories')
            ->assertSuccessful()
            ->assertSee('Action');

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertTableColumnExists('name');
    }

    public function test_customer_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_staff_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_store_resource_renders_columns_and_data(): void
    {
        $store = Store::create(['address' => '47 MyPagila Drive']);

        $this->actingAs($this->pagilaCurator)
            ->get('/pagila/stores')
            ->assertSuccessful()
            ->assertSee('47 MyPagila Drive');

        Livewire::test(ListStores::class)
            ->assertCanSeeTableRecords([$store])
            ->assertTableColumnExists('id')
            ->assertTableColumnExists('address');
    }

    public function test_rental_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_payment_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_inventory_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_language_resource_renders_columns_and_data(): void
    {
        $language = Language::create(['name' => 'English']);

        $this->actingAs($this->pagilaCurator)
            ->get('/pagila/languages')
            ->assertSuccessful()
            ->assertSee('English');

        Livewire::test(ListLanguages::class)
            ->assertCanSeeTableRecords([$language])
            ->assertTableColumnExists('name');
    }
}
