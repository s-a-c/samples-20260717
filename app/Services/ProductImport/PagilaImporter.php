<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\Mapping\Pagila\PagilaProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class PagilaImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
        private PortfolioViewRecreator $viewRecreator,
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

        $sourceSchema = 'pagila_source';
        $stagingSchema = 'pagila_staging';

        try {
            SourceSchemaBuilder::create('pagila');
            $sourceLoaded = $this->processSourceRows($sourceSchema);

            app(StagingSchemaBuilder::class)->build('pagila');

            if ($sourceLoaded) {
                app(PagilaProductMapper::class)->load($sourceSchema, $stagingSchema);
            }

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS pagila CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO pagila;");
                $this->viewRecreator->recreate();
            });

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load the Pagila schema and data dumps into the isolated source schema.
     */
    private function processSourceRows(string $sourceSchema): bool
    {
        $manifest = $this->getManifest();

        if ($manifest === null) {
            return false;
        }

        $schemaPath = $this->getSourceFilePath($manifest['schema_filename']);
        $dataPath = $this->getSourceFilePath($manifest['data_filename']);

        if ($schemaPath === null || $dataPath === null || ! File::exists($schemaPath) || ! File::exists($dataPath)) {
            return false;
        }

        $this->pgReader->executeMultiFile([$schemaPath, $dataPath], $sourceSchema);

        return $this->sourceHasTables($sourceSchema);
    }

    private function sourceHasTables(string $sourceSchema): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $sourceSchema)
            ->exists();
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
