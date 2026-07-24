<?php

namespace Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
}
