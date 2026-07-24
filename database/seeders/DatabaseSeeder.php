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
        $email = env('OPERATOR_EMAIL');
        $password = env('OPERATOR_PASSWORD');
        $name = env('OPERATOR_NAME', 'System Operator');

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            return;
        }

        app(ProvisionOperator::class)->handle($name, $email, $password);
    }
}
