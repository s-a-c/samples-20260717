<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

covers(User::class);

dataset('global_roles', fn () => [
    'system operator' => ['super_admin', ['/admin', '/chinook', '/northwind', '/pagila'], []],
    'chinook curator' => ['chinook_curator', ['/chinook'], ['/admin', '/northwind', '/pagila']],
    'northwind curator' => ['northwind_curator', ['/northwind'], ['/admin', '/chinook', '/pagila']],
    'pagila curator' => ['pagila_curator', ['/pagila'], ['/admin', '/chinook', '/northwind']],
]);

test('each baseline role panel access matrix', function (string $role, array $allowedPaths, array $forbiddenPaths) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role, 'web'));

    foreach ($allowedPaths as $path) {
        $this->actingAs($user)->get($path)->assertSuccessful();
    }

    foreach ($forbiddenPaths as $path) {
        $this->actingAs($user)->get($path)->assertForbidden();
    }
})->with('global_roles');
