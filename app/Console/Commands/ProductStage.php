<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ProductStage extends Command
{
    protected $signature = 'product:stage {product : The product key (chinook, northwind, pagila)}';

    protected $description = 'Build the migration-backed staging schema for a product';

    public function handle(StagingSchemaBuilder $builder): int
    {
        $product = mb_strtolower((string) $this->argument('product'));

        try {
            $builder->build($product);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Staging schema '{$product}_staging' built successfully.");

        return self::SUCCESS;
    }
}
