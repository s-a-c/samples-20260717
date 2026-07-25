<?php

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class PostgresSourceReader
{
    public function executeSqlDump(string $filePath, string $targetSchema, array $excludePatterns = []): void
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("SQL file not found at: {$filePath}");
        }

        $content = File::get($filePath);

        // Remove psql meta-commands (\c, \connect, etc.)
        $content = preg_replace('/^\\\\[a-z].*$/m', '', $content);

        // Remove CREATE DATABASE statements
        $content = preg_replace('/CREATE\s+DATABASE\s+\w+;/i', '', $content);
        $content = preg_replace('/DROP\s+DATABASE\s+IF\s+EXISTS\s+\w+;/i', '', $content);

        // Remove SET statements that are problematic
        $content = preg_replace('/SET\s+\w+.*?;/i', '', $content);

        // Rewrite public. references to target schema
        $content = str_replace('public.', "{$targetSchema}.", $content);

        // Split into individual statements
        $statements = preg_split('/;\s*[\r\n]+/', $content);

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }

            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $stmt)) {
                    $skip = true;
                    break;
                }
            }

            if (! $skip) {
                try {
                    DB::statement($stmt);
                } catch (\Throwable $e) {
                    // Log but continue - some statements may fail due to dependencies
                    report($e);
                }
            }
        }
    }

    public function executeMultiFile(array $filePaths, string $targetSchema): void
    {
        foreach ($filePaths as $filePath) {
            $this->executeSqlDump($filePath, $targetSchema);
        }
    }
}
