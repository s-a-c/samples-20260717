<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProductReset\ResetConfirmationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

final class ProductConfirm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:confirm {product : chinook|northwind|pagila}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mint a single-use reset confirmation token for a product reset';

    /**
     * Execute the console command.
     */
    public function handle(ResetConfirmationService $confirmationService): int
    {
        $product = mb_strtolower($this->argument('product'));

        Role::findOrCreate('super_admin', 'web');

        /** @var User|null $operator */
        $operator = User::role('super_admin')->first();

        if ($operator === null) {
            $operator = User::first();
        }

        if ($operator === null) {
            /** @var User $operator */
            $operator = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
            ]);
            $operator->assignRole('super_admin');
        }

        $manifestPath = database_path("sources/{$product}.php");
        $sha256 = 'unknown_sha256';
        $commit = 'unknown_commit';

        if (File::exists($manifestPath)) {
            /** @var array{digest?: string, commit_sha?: string} $manifest */
            $manifest = require $manifestPath;
            $sha256 = $manifest['digest'] ?? $sha256;
            $commit = $manifest['commit_sha'] ?? $commit;
        }

        $token = $confirmationService->mint($operator, $product, $sha256, $commit);

        $this->info("Confirmation token minted for {$product}: {$token}");

        return self::SUCCESS;
    }
}
