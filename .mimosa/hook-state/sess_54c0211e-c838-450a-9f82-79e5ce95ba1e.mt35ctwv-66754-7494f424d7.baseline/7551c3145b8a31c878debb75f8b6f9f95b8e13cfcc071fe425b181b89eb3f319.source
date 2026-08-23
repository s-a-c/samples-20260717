<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Load PostgreSQL dumps into an isolated source schema.
 *
 * Dumps are executed as one database unit rather than split on semicolons;
 * PostgreSQL function bodies, dollar-quoted strings, and COPY/INSERT data can
 * legally contain semicolons. The transaction also prevents a failed dump
 * from leaving the caller's PostgreSQL connection in an aborted state.
 */
class PostgresSourceReader
{
    /**
     * Execute a complete SQL dump against a target schema.
     *
     * @param  list<string>  $excludePatterns  Patterns matching complete dump
     *                                         statements retained for callers
     *                                         that need product-specific skips.
     */
    public function executeSqlDump(string $filePath, string $targetSchema, array $excludePatterns = []): void
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("SQL file not found at: {$filePath}");
        }

        $sql = $this->prepareDump(File::get($filePath), $targetSchema, $excludePatterns);

        $this->executePreparedDump($sql, $targetSchema);
    }

    /**
     * Execute multiple dump files in order within the same transaction.
     *
     * @param  list<string>  $filePaths
     */
    public function executeMultiFile(array $filePaths, string $targetSchema): void
    {
        $sql = '';
        foreach ($filePaths as $filePath) {
            if (! File::exists($filePath)) {
                throw new RuntimeException("SQL file not found at: {$filePath}");
            }

            $sql .= $this->prepareDump(File::get($filePath), $targetSchema)."\n";
        }

        $this->executePreparedDump($sql, $targetSchema);
    }

    private function executePreparedDump(string $sql, string $targetSchema): void
    {
        $searchPathRow = DB::selectOne('SHOW search_path');
        $searchPathValues = is_object($searchPathRow) ? get_object_vars($searchPathRow) : [];
        $originalSearchPath = $searchPathValues['search_path'] ?? null;

        if (! is_string($originalSearchPath) || $originalSearchPath === '') {
            throw new RuntimeException('Unable to determine the PostgreSQL search path.');
        }

        $quotedSchema = '"'.str_replace('"', '""', $targetSchema).'"';

        DB::transaction(function () use ($sql, $quotedSchema, $originalSearchPath): void {
            DB::statement("SET LOCAL search_path TO {$quotedSchema}");

            try {
                DB::getPdo()->exec($sql);

            } finally {
                DB::statement("SET LOCAL search_path TO {$originalSearchPath}");
            }
        });
    }

    /**
     * @param  list<string>  $excludePatterns
     */
    private function prepareDump(string $content, string $targetSchema, array $excludePatterns = []): string
    {
        $quotedSchema = '"'.str_replace('"', '""', $targetSchema).'"';

        // psql commands are client directives, not SQL accepted by PDO.
        $content = preg_replace('/^\\\\(?:connect|c|[a-z]+)(?:[ \t].*)?$/mi', '', $content) ?? $content;

        // Database/session directives cannot be replayed inside the app's
        // connection and are not part of the source schema's data contract.
        $content = preg_replace('/^\s*(?:CREATE|DROP)\s+DATABASE\b[^;\r\n]*;[ \t]*$/mi', '', $content) ?? $content;
        $content = preg_replace('/^\s*SET\s+(?!check_function_bodies\b)[^;\r\n]*;[ \t]*$/mi', '', $content) ?? $content;
        $content = preg_replace('/^\s*SET\s+check_function_bodies\s*=\s*false\s*;/mi', 'SET LOCAL check_function_bodies = false;', $content) ?? $content;
        $content = preg_replace('/^\s*SELECT\s+pg_catalog\.set_config\([^;\r\n]*;[ \t]*$/mi', '', $content) ?? $content;

        // The upstream dumps target public. Rewrite qualified names and make
        // unqualified DDL/data statements resolve to the isolated schema.
        $content = str_replace('public.', "{$quotedSchema}.", $content);
        $content = preg_replace('/\b(?:CREATE|ALTER)\s+SCHEMA\s+"?public"?\s*[^;]*;\s*/i', '', $content) ?? $content;
        $content = preg_replace('/^\s*ALTER\s+(?:TABLE|SEQUENCE|TYPE|FUNCTION|DOMAIN|AGGREGATE)\b[^;\r\n]*\s+OWNER\s+TO\s+[^;\r\n]*;[ \t]*$/mi', '', $content) ?? $content;
        $content = preg_replace('/^\s*GRANT\s+[^;\r\n]*;[ \t]*$/mi', '', $content) ?? $content;

        // Product-specific callers may retain exclusion rules for compatibility.
        // Apply only to complete simple statements; never split function bodies.
        foreach ($excludePatterns as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        return "SET LOCAL search_path TO {$quotedSchema};\n{$content}";
    }
}
