<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ProductPortfolioCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

covers(ProductPortfolioCard::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));
});

test('refresh stats button is visible on card', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Refresh Stats');
});

test('refresh stats dispatches refresh action and shows spinner', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->call('refreshStats')
        ->assertOk();
});

test('refresh stats shows last refreshed timestamp', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'northwind'])
        ->call('refreshStats')
        ->assertSee('Last refreshed');
});

test('non super admin can see refresh stats button', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $this->actingAs($user);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Refresh Stats');
});
