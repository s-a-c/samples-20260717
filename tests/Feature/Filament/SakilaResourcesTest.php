<?php

namespace Tests\Feature\Filament;

use App\Domain\Sakila\Models\Actor;
use App\Domain\Sakila\Models\Category;
use App\Domain\Sakila\Models\Customer;
use App\Domain\Sakila\Models\Film;
use App\Domain\Sakila\Models\Inventory;
use App\Domain\Sakila\Models\Language;
use App\Domain\Sakila\Models\Payment;
use App\Domain\Sakila\Models\Rental;
use App\Domain\Sakila\Models\Staff;
use App\Domain\Sakila\Models\Store;
use App\Filament\Sakila\Resources\ActorResource\Pages\ListActors;
use App\Filament\Sakila\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Sakila\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Sakila\Resources\FilmResource\Pages\ListFilms;
use App\Filament\Sakila\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Sakila\Resources\LanguageResource\Pages\ListLanguages;
use App\Filament\Sakila\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Sakila\Resources\RentalResource\Pages\ListRentals;
use App\Filament\Sakila\Resources\StaffResource\Pages\ListStaff;
use App\Filament\Sakila\Resources\StoreResource\Pages\ListStores;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SakilaResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $sakilaCurator;

    private User $northwindCurator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sakilaCurator = User::factory()->create();
        $this->sakilaCurator->assignRole(Role::findOrCreate('sakila_curator', 'web'));

        $this->northwindCurator = User::factory()->create();
        $this->northwindCurator->assignRole(Role::findOrCreate('northwind_curator', 'web'));
    }

    public function test_sakila_curator_can_access_all_sakila_resource_list_pages(): void
    {
        $endpoints = [
            '/sakila/actors',
            '/sakila/films',
            '/sakila/categories',
            '/sakila/customers',
            '/sakila/staff',
            '/sakila/stores',
            '/sakila/rentals',
            '/sakila/payments',
            '/sakila/inventories',
            '/sakila/languages',
        ];

        foreach ($endpoints as $endpoint) {
            $this->actingAs($this->sakilaCurator)
                ->get($endpoint)
                ->assertSuccessful();
        }
    }

    public function test_northwind_curator_is_forbidden_from_sakila_pages(): void
    {
        $endpoints = [
            '/sakila/actors',
            '/sakila/films',
            '/sakila/categories',
            '/sakila/customers',
            '/sakila/staff',
            '/sakila/stores',
            '/sakila/rentals',
            '/sakila/payments',
            '/sakila/inventories',
            '/sakila/languages',
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/actors')
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/films')
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/categories')
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
            'email' => 'mary.smith@sakilacustomer.org',
            'active' => true,
        ]);

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/customers')
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
            'email' => 'Mike.Hillyer@sakilastaff.com',
            'active' => true,
            'username' => 'Mike',
        ]);

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/staff')
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
        $store = Store::create(['address' => '47 MySakila Drive']);

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/stores')
            ->assertSuccessful()
            ->assertSee('47 MySakila Drive');

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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/rentals')
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/payments')
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/inventories')
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

        $this->actingAs($this->sakilaCurator)
            ->get('/sakila/languages')
            ->assertSuccessful()
            ->assertSee('English');

        Livewire::test(ListLanguages::class)
            ->assertCanSeeTableRecords([$language])
            ->assertTableColumnExists('name');
    }
}
