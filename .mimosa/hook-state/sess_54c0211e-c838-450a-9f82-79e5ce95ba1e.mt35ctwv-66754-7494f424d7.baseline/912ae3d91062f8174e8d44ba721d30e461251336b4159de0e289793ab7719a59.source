<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ChinookImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
        private PortfolioViewRecreator $viewRecreator,
    ) {}

    /**
     * Execute Chinook import into PostgreSQL schema.
     *
     * @return array{success: bool, error?: string}
     */
    public function import(bool $dryRun = false, ?ResetRun $run = null): array
    {
        if ($dryRun) {
            return ['success' => true];
        }

        $sourceSchema = 'chinook_source';
        $stagingSchema = 'chinook_staging';

        try {
            SourceSchemaBuilder::create('chinook');
            $sourceLoaded = $this->processSourceRows($sourceSchema);

            app(StagingSchemaBuilder::class)->build('chinook');

            if ($sourceLoaded) {
                app(ChinookProductMapper::class)->load($sourceSchema, $stagingSchema);
            }

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO chinook;");
                $this->viewRecreator->recreate();
            });

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load upstream rows into the isolated source schema.
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
        $manifestPath = database_path('sources/chinook.php');

        if (! File::exists($manifestPath)) {
            return null;
        }

        /** @var array{product: string, commit_sha: string, filename: string} $manifest */
        $manifest = require $manifestPath;

        return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$manifest['filename']}");
    }
}
