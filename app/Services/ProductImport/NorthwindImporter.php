<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\Mapping\Northwind\NorthwindProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class NorthwindImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
        private PortfolioViewRecreator $viewRecreator,
    ) {}

    /**
     * Execute Northwind import into PostgreSQL schema.
     *
     * @return array{success: bool, error?: string}
     */
    public function import(bool $dryRun = false, ?ResetRun $run = null): array
    {
        if ($dryRun) {
            return ['success' => true];
        }

        $sourceSchema = 'northwind_source';
        $stagingSchema = 'northwind_staging';

        try {
            SourceSchemaBuilder::create('northwind');
            $sourceLoaded = $this->processSourceRows($sourceSchema);

            app(StagingSchemaBuilder::class)->build('northwind');

            if ($sourceLoaded) {
                app(NorthwindProductMapper::class)->load($sourceSchema, $stagingSchema);
            }

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS northwind CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO northwind;");
                $this->viewRecreator->recreate();
            });

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process source rows into staging schema.
     */
    private function processSourceRows(string $sourceSchema): bool
    {
        $sourceFile = $this->getSourceFilePath();

        if ($sourceFile === null || ! File::exists($sourceFile)) {
            return false;
        }

        $this->pgReader->executeSqlDump($sourceFile, $sourceSchema);

        return $this->sourceHasTables($sourceSchema);
    }

    private function sourceHasTables(string $sourceSchema): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $sourceSchema)
            ->exists();
    }

    private function getSourceFilePath(): ?string
    {
        $manifestPath = database_path('sources/northwind.php');

        if (! File::exists($manifestPath)) {
            return null;
        }

        /** @var array{product: string, commit_sha: string, filename: string} $manifest */
        $manifest = require $manifestPath;

        return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$manifest['filename']}");
    }
}
