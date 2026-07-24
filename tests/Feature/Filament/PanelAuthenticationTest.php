<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function panelPaths(): array
    {
        return [
            'admin' => ['/admin'],
            'chinook' => ['/chinook'],
            'northwind' => ['/northwind'],
            'sakila' => ['/sakila'],
        ];
    }

    #[DataProvider('panelPaths')]
    public function test_guests_are_redirected_to_fortify_login(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_all_panels(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        foreach (['/admin', '/chinook', '/northwind', '/sakila'] as $path) {
            $this->actingAs($user)->get($path)->assertSuccessful();
        }
    }

    public function test_chinook_curator_can_access_chinook_panel_and_is_forbidden_from_others(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

        $this->actingAs($user)->get('/chinook')->assertSuccessful();

        foreach (['/admin', '/northwind', '/sakila'] as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }

    public function test_northwind_curator_can_access_northwind_panel_and_is_forbidden_from_others(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('northwind_curator', 'web'));

        $this->actingAs($user)->get('/northwind')->assertSuccessful();

        foreach (['/admin', '/chinook', '/sakila'] as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }

    public function test_sakila_curator_can_access_sakila_panel_and_is_forbidden_from_others(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('sakila_curator', 'web'));

        $this->actingAs($user)->get('/sakila')->assertSuccessful();

        foreach (['/admin', '/chinook', '/northwind'] as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }
}
