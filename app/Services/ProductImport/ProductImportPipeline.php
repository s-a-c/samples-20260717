<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use App\Services\ProductReset\ResetEvidence;
use Illuminate\Support\Str;

class ProductImportPipeline
{
    public function __construct(
        private ChinookImporter $chinookImporter,
        private NorthwindImporter $northwindImporter,
        private PagilaImporter $pagilaImporter,
        private ImportInvariants $invariants,
        private EmbeddingDrain $embeddingDrain,
    ) {}

    /**
     * Run the import pipeline for a product.
     *
     * @return array{success: bool, error?: string, run_id?: string}
     */
    public function run(string $product, bool $dryRun = false): array
    {
        $product = mb_strtolower($product);

        if (! in_array($product, ['chinook', 'northwind', 'pagila'], true)) {
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

        $evidence = ResetEvidence::create();
        $evidence->setSection('metadata', [
            'requester' => 'system',
            'product' => $product,
            'run_id' => $run->id,
        ]);

        $importer = match ($product) {
            'chinook' => $this->chinookImporter,
            'northwind' => $this->northwindImporter,
            'pagila' => $this->pagilaImporter,
        };

        $result = $importer->import(dryRun: false, run: $run);

        if ($result['success']) {
            // Evaluate invariants
            $invariantResult = $this->invariants->evaluate($product);
            $evidence->setSection('post_reset_verification', [
                'invariants' => $invariantResult,
            ]);

            if (! $invariantResult['passed']) {
                $run->update([
                    'status' => 'failed',
                    'current_phase' => 'invariant_check',
                    'evidence' => $evidence->toArray(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Invariant check failed: '.implode(', ', $invariantResult['failures']),
                    'run_id' => $run->id,
                ];
            }

            // Drain embeddings
            $drainResult = $this->embeddingDrain->drain($product);
            $evidence->setSection('execution_summary', [
                'embedding_drain' => $drainResult,
            ]);

            $run->update([
                'status' => 'succeeded',
                'current_phase' => 'complete',
                'evidence' => $evidence->toArray(),
            ]);
            $result['run_id'] = $run->id;
        } else {
            $evidence->setSection('execution_summary', [
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            $run->update([
                'status' => 'failed',
                'current_phase' => 'failed',
                'evidence' => $evidence->toArray(),
            ]);
        }

        return $result;
    }
}
