<?php

declare(strict_types=1);

use App\Actions\Operators\ProvisionOperator;
use App\Models\User;
use Spatie\Permission\Models\Role;

covers(ProvisionOperator::class);

test('provision operator handle creates a new user with super_admin role and personal team', function () {
    $action = app(ProvisionOperator::class);

    $user = $action->handle(
        name: 'Jane Operator',
        email: 'jane@samples.local',
        password: 'secret-password',
    );

    expect($user->email)->toBe('jane@samples.local')
        ->and($user->name)->toBe('Jane Operator')
        ->and($user->hasRole('super_admin'))->toBeTrue()
        ->and($user->personalTeam())->not->toBeNull()
        ->and($user->current_team_id)->not->toBeNull();
});

test('provision operator handle is idempotent and updates name on re-provision', function () {
    $action = app(ProvisionOperator::class);

    $user1 = $action->handle('First Name', 'idempotent@samples.local', 'pw1');
    $user2 = $action->handle('Updated Name', 'idempotent@samples.local', 'pw2');

    expect($user2->id)->toBe($user1->id)
        ->and($user2->name)->toBe('Updated Name')
        ->and(User::where('email', 'idempotent@samples.local')->count())->toBe(1);
});

test('provision operator handle assigns super_admin role only once', function () {
    Role::findOrCreate('super_admin', 'web');

    $action = app(ProvisionOperator::class);
    $user = $action->handle('Single Role', 'singlerole@samples.local', 'pw');

    expect($user->roles()->where('name', 'super_admin')->count())->toBe(1);

    $action->handle('Single Role', 'singlerole@samples.local', 'pw');
    $user->refresh();

    expect($user->roles()->where('name', 'super_admin')->count())->toBe(1);
});

test('provision operator execute delegates to handle with reversed signature', function () {
    $action = app(ProvisionOperator::class);

    $user = $action->execute(
        email: 'exec@samples.local',
        password: 'pw',
        name: 'Exec User',
    );

    expect($user->email)->toBe('exec@samples.local')
        ->and($user->name)->toBe('Exec User');
});

test('provision operator execute uses default name when omitted', function () {
    $action = app(ProvisionOperator::class);

    $user = $action->execute('default@samples.local', 'pw');

    expect($user->name)->toBe('System Operator');
});

test('provision operator logs activity on creation', function () {
    $action = app(ProvisionOperator::class);

    $action->handle('Audited', 'audited@samples.local', 'pw');

    expect(Spatie\Activitylog\Models\Activity::where('event', 'operator_created')->count())->toBe(1);
});
