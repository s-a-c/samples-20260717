<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class PagilaImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
    ) {}

    /**
     * Execute Pagila import into PostgreSQL schema.
     *
     * @return array{success: bool, error?: string}
     */
    public function import(bool $dryRun = false, ?ResetRun $run = null): array
    {
        if ($dryRun) {
            return ['success' => true];
        }

        $stagingSchema = 'pagila_staging';

        try {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$stagingSchema};");

            $this->processSourceRows($stagingSchema);

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS pagila CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO pagila;");
            });

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process source files into staging schema.
     */
    private function processSourceRows(string $stagingSchema): void
    {
        $manifest = $this->getManifest();

        if ($manifest === null) {
            return;
        }

        $productDir = $manifest['product'];
        $commitSha = $manifest['commit_sha'];
        $schemaPath = $this->getSourceFilePath($manifest['schema_filename']);
        $dataPath = $this->getSourceFilePath($manifest['data_filename']);

        if ($schemaPath !== null && File::exists($schemaPath)) {
            $this->pgReader->executeSqlDump($schemaPath, $stagingSchema);
        }

        if ($dataPath !== null && File::exists($dataPath)) {
            $this->pgReader->executeSqlDump($dataPath, $stagingSchema);
        }
    }

    /**
     * @return array{product: string, commit_sha: string, schema_filename: string, data_filename: string}|null
     */
    private function getManifest(): ?array
    {
        $manifestPath = database_path('sources/pagila.php');

        if (! File::exists($manifestPath)) {
            return null;
        }

        /** @var array{product: string, commit_sha: string, schema_filename: string, data_filename: string} $manifest */
        return require $manifestPath;
    }

    private function getSourceFilePath(string $filename): ?string
    {
        $manifest = $this->getManifest();

        if ($manifest === null) {
            return null;
        }

        return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$filename}");
    }
}
