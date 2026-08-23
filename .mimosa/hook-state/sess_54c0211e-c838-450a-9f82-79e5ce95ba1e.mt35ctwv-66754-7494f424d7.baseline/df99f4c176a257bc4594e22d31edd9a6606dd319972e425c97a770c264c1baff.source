<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

covers(User::class);

test('each baseline role panel access matrix', function (string $role, string $path, bool $allowed) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role, 'web'));

    $response = $this->actingAs($user)->get($path);

    if ($allowed) {
        $response->assertSuccessful();
    } else {
        $response->assertForbidden();
    }
})->with([
    'super_admin can access admin' => ['super_admin', '/admin', true],
    'super_admin can access chinook' => ['super_admin', '/chinook', true],
    'super_admin can access northwind' => ['super_admin', '/northwind', true],
    'super_admin can access pagila' => ['super_admin', '/pagila', true],

    'chinook_curator can access chinook' => ['chinook_curator', '/chinook', true],
    'chinook_curator blocked from admin' => ['chinook_curator', '/admin', false],
    'chinook_curator blocked from northwind' => ['chinook_curator', '/northwind', false],
    'chinook_curator blocked from pagila' => ['chinook_curator', '/pagila', false],

    'northwind_curator can access northwind' => ['northwind_curator', '/northwind', true],
    'northwind_curator blocked from admin' => ['northwind_curator', '/admin', false],
    'northwind_curator blocked from chinook' => ['northwind_curator', '/chinook', false],
    'northwind_curator blocked from pagila' => ['northwind_curator', '/pagila', false],

    'pagila_curator can access pagila' => ['pagila_curator', '/pagila', true],
    'pagila_curator blocked from admin' => ['pagila_curator', '/admin', false],
    'pagila_curator blocked from chinook' => ['pagila_curator', '/chinook', false],
    'pagila_curator blocked from northwind' => ['pagila_curator', '/northwind', false],
]);
