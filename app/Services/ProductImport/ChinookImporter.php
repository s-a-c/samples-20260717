<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

final class ChinookImporter
{
    public function __construct(
        private PostgresSourceReader $pgReader,
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

        $stagingSchema = 'chinook_staging';

        try {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$stagingSchema};");

            $this->processSourceRows($stagingSchema);

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO chinook;");
            });

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process source rows into staging schema.
     */
    private function processSourceRows(string $stagingSchema): void
    {
        $sourceFile = $this->getSourceFilePath();

        if ($sourceFile !== null && File::exists($sourceFile)) {
            $excludePatterns = [
                '/CREATE\s+DATABASE/i',
                '/\\\\c/i',
            ];
            $this->pgReader->executeSqlDump($sourceFile, $stagingSchema, $excludePatterns);
        }
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
