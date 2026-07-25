<?php

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use Illuminate\Support\Str;

final class ProductImportPipeline
{
    public function __construct(
        protected ChinookImporter $chinookImporter,
        protected NorthwindImporter $northwindImporter,
        protected SakilaImporter $sakilaImporter,
    ) {}

    /**
     * Run the import pipeline for a product.
     *
     * @return array{success: bool, error?: string, run_id?: string}
     */
    public function run(string $product, bool $dryRun = false): array
    {
        $product = strtolower($product);

        if (! in_array($product, ['chinook', 'northwind', 'sakila'], true)) {
            return ['success' => false, 'error' => "Unknown product: {$product}"];
        }

        if ($dryRun) {
            return ['success' => true];
        }

        $run = ResetRun::create([
            'id' => (string) Str::uuid7(),
            'product' => $product,
            'kind' => 'import',
            'status' => 'running',
            'current_phase' => 'staging',
        ]);

        $importer = match ($product) {
            'chinook' => $this->chinookImporter,
            'northwind' => $this->northwindImporter,
            'sakila' => $this->sakilaImporter,
        };

        $result = $importer->import(dryRun: false, run: $run);

        if ($result['success']) {
            $run->update([
                'status' => 'succeeded',
                'current_phase' => 'complete',
            ]);
            $result['run_id'] = $run->id;
        } else {
            $run->update([
                'status' => 'failed',
                'current_phase' => 'failed',
                'evidence' => ['error' => $result['error'] ?? 'Unknown error'],
            ]);
        }

        return $result;
    }
}
