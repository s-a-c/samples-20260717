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
     * @return array<string, array{string, array<string>, array<string>}>
     */
    public static function globalRoles(): array
    {
        return [
            'system operator' => ['super_admin', ['/admin', '/chinook', '/northwind', '/sakila'], []],
            'chinook curator' => ['chinook_curator', ['/chinook'], ['/admin', '/northwind', '/sakila']],
            'northwind curator' => ['northwind_curator', ['/northwind'], ['/admin', '/chinook', '/sakila']],
            'sakila curator' => ['sakila_curator', ['/sakila'], ['/admin', '/chinook', '/northwind']],
        ];
    }

    /**
     * @param  array<string>  $allowedPaths
     * @param  array<string>  $forbiddenPaths
     */
    #[DataProvider('globalRoles')]
    public function test_each_baseline_role_panel_access_matrix(string $role, array $allowedPaths, array $forbiddenPaths): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));

        foreach ($allowedPaths as $path) {
            $this->actingAs($user)->get($path)->assertSuccessful();
        }

        foreach ($forbiddenPaths as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }
}
