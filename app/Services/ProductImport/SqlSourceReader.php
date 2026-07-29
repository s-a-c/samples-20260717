<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class SqlSourceReader
{
    /**
     * Read SQL file and return raw content.
     */
    public function getContent(string $filePath): string
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("SQL source file not found at: {$filePath}");
        }

        return File::get($filePath);
    }

    /**
     * Read SQL file and split into executable statements.
     *
     * @return array<int, string>
     */
    public function getStatements(string $filePath): array
    {
        $content = $this->getContent($filePath);

        /** @var list<string>|false $statements */
        $statements = preg_split('/;\s*[\r\n]+/', $content);

        if (! is_array($statements)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', $statements),
            fn (string $stmt) => $stmt !== '' && ! str_starts_with($stmt, '--')
        ));
    }
}
