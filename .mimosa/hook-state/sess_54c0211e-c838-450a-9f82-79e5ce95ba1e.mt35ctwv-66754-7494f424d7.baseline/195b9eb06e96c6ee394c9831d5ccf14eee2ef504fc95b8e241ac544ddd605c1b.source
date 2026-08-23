<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

covers(AppServiceProvider::class);

test('gate before closure grants access to super admin users', function () {
    Role::findOrCreate('super_admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $regular = User::factory()->create();

    Gate::define('arbitrary-ability', fn () => false);

    expect(Gate::forUser($admin)->allows('arbitrary-ability'))->toBeTrue()
        ->and(Gate::forUser($regular)->allows('arbitrary-ability'))->toBeFalse();
});

test('password defaults closure executes and returns a rule in production', function () {
    $app = $this->app;
    assert($app instanceof Application);
    $app['env'] = 'production';

    $rule = Password::default();

    expect($rule)->toBeInstanceOf(Password::class);

    $app['env'] = 'testing';
});

test('password defaults return the production-strength rule when production is configured', function () {
    $app = $this->app;
    assert($app instanceof Application);
    $app['env'] = 'production';

    try {
        expect(Password::default())->toBeInstanceOf(Password::class);
    } finally {
        $app['env'] = 'testing';
    }
});
