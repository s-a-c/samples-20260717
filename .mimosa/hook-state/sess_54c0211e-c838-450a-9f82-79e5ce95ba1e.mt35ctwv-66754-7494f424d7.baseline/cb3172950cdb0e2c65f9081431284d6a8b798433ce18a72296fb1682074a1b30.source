<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

covers(User::class);

dataset('panel_paths', fn () => [
    'admin' => ['/admin'],
    'chinook' => ['/chinook'],
    'northwind' => ['/northwind'],
    'pagila' => ['/pagila'],
]);

test('guests are redirected to fortify login', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with('panel_paths');

test('super admin can access all panels', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));

    foreach (['/admin', '/chinook', '/northwind', '/pagila'] as $path) {
        $this->actingAs($user)->get($path)->assertSuccessful();
    }
});

test('chinook curator can access chinook panel and is forbidden from others', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $this->actingAs($user)->get('/chinook')->assertSuccessful();

    foreach (['/admin', '/northwind', '/pagila'] as $path) {
        $this->actingAs($user)->get($path)->assertForbidden();
    }
});

test('northwind curator can access northwind panel and is forbidden from others', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('northwind_curator', 'web'));

    $this->actingAs($user)->get('/northwind')->assertSuccessful();

    foreach (['/admin', '/chinook', '/pagila'] as $path) {
        $this->actingAs($user)->get($path)->assertForbidden();
    }
});

test('pagila curator can access pagila panel and is forbidden from others', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('pagila_curator', 'web'));

    $this->actingAs($user)->get('/pagila')->assertSuccessful();

    foreach (['/admin', '/chinook', '/northwind'] as $path) {
        $this->actingAs($user)->get($path)->assertForbidden();
    }
});
