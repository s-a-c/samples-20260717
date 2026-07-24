<?php

namespace Database\Seeders;

use App\Actions\Operators\ProvisionOperator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $emailEnv = getenv('OPERATOR_EMAIL');
        $passwordEnv = getenv('OPERATOR_PASSWORD');
        $nameEnv = getenv('OPERATOR_NAME');

        /** @var string|null $email */
        $email = $_ENV['OPERATOR_EMAIL'] ?? ($emailEnv !== false ? $emailEnv : null);
        /** @var string|null $password */
        $password = $_ENV['OPERATOR_PASSWORD'] ?? ($passwordEnv !== false ? $passwordEnv : null);
        /** @var string|null $rawName */
        $rawName = $_ENV['OPERATOR_NAME'] ?? ($nameEnv !== false ? $nameEnv : null);

        $name = is_string($rawName) && $rawName !== '' ? $rawName : 'System Operator';

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            return;
        }

        app(ProvisionOperator::class)->handle($name, $email, $password);
    }
}
