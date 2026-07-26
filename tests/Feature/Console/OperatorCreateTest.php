<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

covers(App\Console\Commands\OperatorCreate::class);

function withOperatorEnvironment(): void
{
    $_ENV['OPERATOR_NAME'] = 'System Operator';
    $_ENV['OPERATOR_EMAIL'] = 'operator@example.com';
    $_ENV['OPERATOR_PASSWORD'] = 'operator-password';
    putenv('OPERATOR_NAME=System Operator');
    putenv('OPERATOR_EMAIL=operator@example.com');
    putenv('OPERATOR_PASSWORD=operator-password');
}

function withoutOperatorEnvironment(): void
{
    putenv('OPERATOR_NAME=');
    putenv('OPERATOR_EMAIL=');
    putenv('OPERATOR_PASSWORD=');
    unset($_ENV['OPERATOR_NAME'], $_ENV['OPERATOR_EMAIL'], $_ENV['OPERATOR_PASSWORD']);
    unset($_SERVER['OPERATOR_NAME'], $_SERVER['OPERATOR_EMAIL'], $_SERVER['OPERATOR_PASSWORD']);
}

test('operator create requires credentials when env and options missing', function () {
    withoutOperatorEnvironment();

    $this->artisan('operator:create')
        ->assertExitCode(1);
});

test('operator create provisions user via options', function () {
    withoutOperatorEnvironment();

    $this->artisan('operator:create', [
        '--email' => 'admin@samples.local',
        '--password' => 'password123',
        '--name' => 'System Operator',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@samples.local')->first();
    $this->assertNotNull($user);
    $this->assertTrue($user->hasRole('super_admin'));
    $this->assertNotNull($user->personalTeam());
    $this->assertSame($user->personalTeam()?->id, $user->current_team_id);
});

test('operator create uses environment variables when options omitted', function () {
    withOperatorEnvironment();

    $this->artisan('operator:create')->assertSuccessful();

    $user = User::query()->where('email', 'operator@example.com')->first();
    $this->assertNotNull($user);
    $this->assertTrue($user->hasRole('super_admin'));
    $this->assertNotNull($user->personalTeam());
    $this->assertSame($user->personalTeam()?->id, $user->current_team_id);

    withoutOperatorEnvironment();
});

test('operator create is idempotent and audited', function () {
    withOperatorEnvironment();

    $this->artisan('operator:create')->assertSuccessful();
    $this->artisan('operator:create')->assertSuccessful();

    $this->assertDatabaseHas('users', ['email' => 'operator@example.com']);
    $this->assertSame(1, User::query()->where('email', 'operator@example.com')->count());
    $this->assertSame(2, Spatie\Activitylog\Models\Activity::query()->where('event', 'operator_created')->count());
    $this->assertTrue(User::query()->where('email', 'operator@example.com')->firstOrFail()->hasRole('super_admin'));

    withoutOperatorEnvironment();
});

test('database seeder seeds roles and default operator', function () {
    withoutOperatorEnvironment();

    $this->seed(Database\Seeders\DatabaseSeeder::class);

    $this->assertTrue(Role::where('name', 'super_admin')->exists());
    $this->assertTrue(Role::where('name', 'chinook_curator')->exists());
    $this->assertTrue(Role::where('name', 'northwind_curator')->exists());
    $this->assertTrue(Role::where('name', 'pagila_curator')->exists());

    $user = User::query()->where('email', 'operator@samples.local')->first();
    $this->assertNotNull($user);
    $this->assertTrue($user->hasRole('super_admin'));
    $this->assertNotNull($user->personalTeam());
});
