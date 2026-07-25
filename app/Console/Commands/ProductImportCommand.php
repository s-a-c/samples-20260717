<?php

namespace App\Console\Commands;

use App\Services\ProductImport\ProductImportPipeline;
use App\Services\ProductReset\ResetConfirmationService;
use Illuminate\Console\Command;

class ProductImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:import {product : chinook|northwind|pagila} {--dry-run} {--force} {--confirm-token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or reset product dataset into PostgreSQL domain schema';

    /**
     * Execute the console command.
     */
    public function handle(ProductImportPipeline $pipeline, ResetConfirmationService $confirmationService): int
    {
        $product = strtolower($this->argument('product'));
        $dryRun = $this->option('dry-run');
        /** @var string|null $confirmToken */
        $confirmToken = $this->option('confirm-token');

        if ($confirmToken !== null && $confirmToken !== '') {
            if (! $confirmationService->verify($confirmToken)) {
                $this->error('Invalid or expired confirmation token.');

                return self::FAILURE;
            }
        }

        $this->info("Starting product import pipeline for '{$product}' (dry-run: ".($dryRun ? 'yes' : 'no').')...');

        $result = $pipeline->run($product, $dryRun);

        if (! $result['success']) {
            $this->error('Import failed: '.($result['error'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info('Import completed successfully.');

        return self::SUCCESS;
    }
}
