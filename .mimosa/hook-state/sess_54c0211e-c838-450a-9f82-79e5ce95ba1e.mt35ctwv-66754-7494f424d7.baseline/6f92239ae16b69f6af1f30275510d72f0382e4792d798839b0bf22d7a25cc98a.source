<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

covers(App\Filament\Admin\Pages\Portfolio::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));
});

test('super admin can access the portfolio page', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful();
});

test('portfolio page renders cards for each product', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful()
        ->assertSee('Chinook')
        ->assertSee('Northwind')
        ->assertSee('Pagila');
});

test('portfolio page displays product descriptions', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful()
        ->assertSee('Digital media store')
        ->assertSee('order-management')
        ->assertSee('DVD rental store');
});

test('portfolio page displays navigation links to product panels', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful()
        ->assertSee('/chinook')
        ->assertSee('/northwind')
        ->assertSee('/pagila');
});

test('portfolio page shows product stats', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful()
        ->assertSee('Tables')
        ->assertSee('Artists')
        ->assertSee('Products')
        ->assertSee('Films')
        ->assertSee('Actors');
});

test('portfolio page displays open panel buttons', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/portfolio')
        ->assertSuccessful()
        ->assertSee('Go to Chinook Panel')
        ->assertSee('Go to Northwind Panel')
        ->assertSee('Go to Pagila Panel');
});

test('guest is redirected to login', function () {
    $this->get('/admin/portfolio')
        ->assertRedirect(route('login'));
});

test('non super admin cannot access portfolio page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $this->actingAs($user)
        ->get('/admin/portfolio')
        ->assertForbidden();
});
