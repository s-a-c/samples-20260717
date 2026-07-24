<?php

namespace App\Console\Commands;

use App\Actions\Operators\ProvisionOperator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('operator:create')]
#[Description('Create or update the System Operator account')]
class OperatorCreate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProvisionOperator $provisionOperator): int
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
            $this->error('OPERATOR_EMAIL and OPERATOR_PASSWORD must be configured.');

            return self::FAILURE;
        }

        $user = $provisionOperator->handle($name, $email, $password);

        $this->info("System Operator ready: {$user->email}");

        return self::SUCCESS;
    }
}
