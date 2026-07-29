<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Operators\ProvisionOperator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('operator:create {--email=} {--password=} {--name=}')]
#[Description('Create or update the System Operator account')]
final class OperatorCreate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProvisionOperator $provisionOperator): int
    {
        $emailOpt = $this->option('email');
        $passwordOpt = $this->option('password');
        $nameOpt = $this->option('name');

        $emailEnv = getenv('OPERATOR_EMAIL');
        $passwordEnv = getenv('OPERATOR_PASSWORD');
        $nameEnv = getenv('OPERATOR_NAME');

        /** @var string|null $email */
        $email = (is_string($emailOpt) && $emailOpt !== '')
            ? $emailOpt
            : ($_ENV['OPERATOR_EMAIL'] ?? ($emailEnv !== false && $emailEnv !== '' ? $emailEnv : null));

        /** @var string|null $password */
        $password = (is_string($passwordOpt) && $passwordOpt !== '')
            ? $passwordOpt
            : ($_ENV['OPERATOR_PASSWORD'] ?? ($passwordEnv !== false && $passwordEnv !== '' ? $passwordEnv : null));

        /** @var string|null $rawName */
        $rawName = (is_string($nameOpt) && $nameOpt !== '')
            ? $nameOpt
            : ($_ENV['OPERATOR_NAME'] ?? ($nameEnv !== false && $nameEnv !== '' ? $nameEnv : null));

        $name = is_string($rawName) && $rawName !== '' ? $rawName : 'System Operator';

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            $this->error('OPERATOR_EMAIL and OPERATOR_PASSWORD must be configured or passed via options.');

            return self::FAILURE;
        }

        $user = $provisionOperator->handle($name, $email, $password);

        $this->info("System Operator ready: {$user->email}");

        return self::SUCCESS;
    }
}
