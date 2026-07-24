<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class OperatorCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_create_requires_credentials(): void
    {
        $this->withoutOperatorEnvironment();

        $this->artisan('operator:create')
            ->assertExitCode(1);
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
