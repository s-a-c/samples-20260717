<?php

namespace App\Services\ProductImport;

use PDO;
use RuntimeException;

class SqliteSourceReader
{
    /**
     * Get a PDO connection to the SQLite database file.
     */
    public function connect(string $filePath): PDO
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("SQLite file not found at: {$filePath}");
        }

        $pdo = new PDO("sqlite:{$filePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Get list of table names in the SQLite database.
     *
     * @return array<int, string>
     */
    public function getTables(string $filePath): array
    {
        $pdo = $this->connect($filePath);
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        /** @var array<int, string> $tables */
        $tables = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        return $tables;
    }

    /**
     * Read all rows from a table in the SQLite database.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readTable(string $filePath, string $tableName): array
    {
        $pdo = $this->connect($filePath);
        $stmt = $pdo->prepare("SELECT * FROM \"{$tableName}\"");
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
