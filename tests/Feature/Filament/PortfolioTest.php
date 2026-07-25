<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));
    }

    public function test_super_admin_can_access_the_portfolio_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful();
    }

    public function test_portfolio_page_renders_cards_for_each_product(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful()
            ->assertSee('Chinook')
            ->assertSee('Northwind')
            ->assertSee('Sakila');
    }

    public function test_portfolio_page_displays_product_descriptions(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful()
            ->assertSee('Digital media store')
            ->assertSee('order-management')
            ->assertSee('DVD rental store');
    }

    public function test_portfolio_page_displays_navigation_links_to_product_panels(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful()
            ->assertSee('/chinook')
            ->assertSee('/northwind')
            ->assertSee('/sakila');
    }

    public function test_portfolio_page_shows_product_stats(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful()
            ->assertSee('Tables')
            ->assertSee('Artists')
            ->assertSee('Products')
            ->assertSee('Films')
            ->assertSee('Actors');
    }

    public function test_portfolio_page_displays_open_panel_buttons(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/portfolio')
            ->assertSuccessful()
            ->assertSee('Go to Chinook Panel')
            ->assertSee('Go to Northwind Panel')
            ->assertSee('Go to Sakila Panel');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/portfolio')
            ->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_access_portfolio_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

        $this->actingAs($user)
            ->get('/admin/portfolio')
            ->assertForbidden();
    }
}
