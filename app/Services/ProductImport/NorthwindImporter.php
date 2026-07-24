<?php

namespace App\Services\ProductImport;

use App\Models\ResetRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class NorthwindImporter
{
    public function __construct(
        protected SourceIdentityRegistry $identityRegistry,
        protected SqlSourceReader $sqlReader,
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

        $stagingSchema = 'northwind_staging';

        try {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$stagingSchema};");

            $this->processSourceRows($stagingSchema);

            DB::transaction(function () use ($stagingSchema) {
                DB::statement('DROP SCHEMA IF EXISTS northwind CASCADE;');
                DB::statement("ALTER SCHEMA {$stagingSchema} RENAME TO northwind;");
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process source rows into staging schema.
     */
    protected function processSourceRows(string $stagingSchema): void
    {
        $sourceFile = $this->getSourceFilePath();

        if ($sourceFile && File::exists($sourceFile)) {
            $statements = $this->sqlReader->getStatements($sourceFile);
            foreach ($statements as $stmt) {
                if (preg_match('/INSERT INTO ["\']?(\w+)["\']?/i', $stmt, $matches)) {
                    $table = $matches[1];
                    $entity = "northwind.{$table}";
                    $this->identityRegistry->getOrMint($entity, ['stmt' => md5($stmt)]);
                }
            }
        }
    }

    protected function getSourceFilePath(): ?string
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
