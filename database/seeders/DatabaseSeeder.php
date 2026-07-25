<?php

namespace Database\Seeders;

use App\Actions\Operators\ProvisionOperator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('chinook_curator', 'web');
        Role::findOrCreate('northwind_curator', 'web');
        Role::findOrCreate('pagila_curator', 'web');

        $emailEnv = getenv('OPERATOR_EMAIL');
        $passwordEnv = getenv('OPERATOR_PASSWORD');
        $nameEnv = getenv('OPERATOR_NAME');

        /** @var string|null $email */
        $email = $_ENV['OPERATOR_EMAIL'] ?? ($emailEnv !== false && $emailEnv !== '' ? $emailEnv : 'operator@samples.local');
        /** @var string|null $password */
        $password = $_ENV['OPERATOR_PASSWORD'] ?? ($passwordEnv !== false && $passwordEnv !== '' ? $passwordEnv : 'password');
        /** @var string|null $rawName */
        $rawName = $_ENV['OPERATOR_NAME'] ?? ($nameEnv !== false && $nameEnv !== '' ? $nameEnv : null);

        $name = is_string($rawName) && $rawName !== '' ? $rawName : 'System Operator';

        app(ProvisionOperator::class)->handle($name, $email, $password);
    }
}
