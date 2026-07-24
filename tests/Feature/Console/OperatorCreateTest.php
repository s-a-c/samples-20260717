<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperatorCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_create_requires_credentials_when_env_and_options_missing(): void
    {
        $this->withoutOperatorEnvironment();

        $this->artisan('operator:create')
            ->assertExitCode(1);
    }

    public function test_operator_create_provisions_user_via_options(): void
    {
        $this->withoutOperatorEnvironment();

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
    }

    public function test_operator_create_uses_environment_variables_when_options_omitted(): void
    {
        $this->withOperatorEnvironment();

        $this->artisan('operator:create')->assertSuccessful();

        $user = User::query()->where('email', 'operator@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertNotNull($user->personalTeam());
        $this->assertSame($user->personalTeam()?->id, $user->current_team_id);

        $this->withoutOperatorEnvironment();
    }

    public function test_operator_create_is_idempotent_and_audited(): void
    {
        $this->withOperatorEnvironment();

        $this->artisan('operator:create')->assertSuccessful();
        $this->artisan('operator:create')->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'operator@example.com']);
        $this->assertSame(1, User::query()->where('email', 'operator@example.com')->count());
        $this->assertSame(2, Activity::query()->where('event', 'operator_created')->count());
        $this->assertTrue(User::query()->where('email', 'operator@example.com')->firstOrFail()->hasRole('super_admin'));

        $this->withoutOperatorEnvironment();
    }

    public function test_database_seeder_seeds_roles_and_default_operator(): void
    {
        $this->withoutOperatorEnvironment();

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Role::where('name', 'super_admin')->exists());
        $this->assertTrue(Role::where('name', 'chinook_curator')->exists());
        $this->assertTrue(Role::where('name', 'northwind_curator')->exists());
        $this->assertTrue(Role::where('name', 'sakila_curator')->exists());

        $user = User::query()->where('email', 'operator@samples.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertNotNull($user->personalTeam());
    }

    protected function withOperatorEnvironment(): void
    {
        $_ENV['OPERATOR_NAME'] = 'System Operator';
        $_ENV['OPERATOR_EMAIL'] = 'operator@example.com';
        $_ENV['OPERATOR_PASSWORD'] = 'operator-password';
        putenv('OPERATOR_NAME=System Operator');
        putenv('OPERATOR_EMAIL=operator@example.com');
        putenv('OPERATOR_PASSWORD=operator-password');
    }

    protected function withoutOperatorEnvironment(): void
    {
        putenv('OPERATOR_NAME=');
        putenv('OPERATOR_EMAIL=');
        putenv('OPERATOR_PASSWORD=');
        unset($_ENV['OPERATOR_NAME'], $_ENV['OPERATOR_EMAIL'], $_ENV['OPERATOR_PASSWORD']);
        unset($_SERVER['OPERATOR_NAME'], $_SERVER['OPERATOR_EMAIL'], $_SERVER['OPERATOR_PASSWORD']);
    }
}
