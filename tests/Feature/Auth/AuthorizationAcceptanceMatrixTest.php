<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationAcceptanceMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function globalRoles(): array
    {
        return [
            'system operator' => ['super_admin'],
            'chinook curator' => ['chinook_curator'],
            'northwind curator' => ['northwind_curator'],
            'sakila curator' => ['sakila_curator'],
        ];
    }

    #[DataProvider('globalRoles')]
    public function test_each_baseline_role_can_enter_each_panel(string $role): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));

        foreach (['/admin', '/chinook', '/northwind', '/sakila'] as $path) {
            $this->actingAs($user)->get($path)->assertSuccessful();
        }
    }
}
