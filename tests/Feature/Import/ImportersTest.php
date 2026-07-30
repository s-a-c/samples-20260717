<?php

declare(strict_types=1);

use App\Services\ProductImport\ChinookImporter;
use App\Services\ProductImport\NorthwindImporter;
use App\Services\ProductImport\PagilaImporter;
use App\Services\ProductImport\PostgresSourceReader;
use App\Services\ProductImport\SqliteSourceReader;
use App\Services\ProductImport\SqlSourceReader;
use Illuminate\Support\Facades\File;
use Mockery;
use PDO;
use RuntimeException;

covers(
    ChinookImporter::class,
    NorthwindImporter::class,
    PagilaImporter::class,
    PostgresSourceReader::class,
    SqlSourceReader::class,
    SqliteSourceReader::class,
);

// ---------------------------------------------------------------------------
// ChinookImporter / NorthwindImporter / PagilaImporter — dry-run
// ---------------------------------------------------------------------------

test('chinook importer dry-run returns success without touching the database', function () {
    $importer = app(ChinookImporter::class);

    $result = $importer->import(dryRun: true);

    expect($result)->toBe(['success' => true]);
});

test('northwind importer dry-run returns success without touching the database', function () {
    $importer = app(NorthwindImporter::class);

    $result = $importer->import(dryRun: true);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer dry-run returns success without touching the database', function () {
    $importer = app(PagilaImporter::class);

    $result = $importer->import(dryRun: true);

    expect($result)->toBe(['success' => true]);
});

test('chinook importer full import completes successfully when no source file is cached', function () {
    $importer = app(ChinookImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('northwind importer full import completes successfully when no source file is cached', function () {
    $importer = app(NorthwindImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer full import completes successfully when no source file is cached', function () {
    $importer = app(PagilaImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

// ---------------------------------------------------------------------------
// Importers — source file present, failure, and missing-manifest paths
// ---------------------------------------------------------------------------

function sourceFilePath(string $product, string $filename): string
{
    $manifest = require database_path("sources/{$product}.php");

    return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$filename}");
}

test('chinook importer processes source rows when the cached source file exists', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->withArgs(fn (string $file, string $schema, ?array $patterns = null) => true)
        ->once();

    $importer = new ChinookImporter($pgReader);

    $manifest = require database_path('sources/chinook.php');
    $path = sourceFilePath('chinook', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "SELECT 1;\n");

    try {
        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        File::delete($path);
    }
});

test('chinook importer returns failure when executeSqlDump throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->andThrow(new RuntimeException('pg_dump failed'));

    $manifest = require database_path('sources/chinook.php');
    $path = sourceFilePath('chinook', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "SELECT 1;\n");

    try {
        $importer = new ChinookImporter($pgReader);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('pg_dump failed');
    } finally {
        File::delete($path);
    }
});

test('chinook importer handles a missing manifest gracefully', function () {
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/chinook.php'));

    $importer = app(ChinookImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('northwind importer processes source rows when the cached source file exists', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')->once();

    $manifest = require database_path('sources/northwind.php');
    $path = sourceFilePath('northwind', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "SELECT 1;\n");

    try {
        $importer = new NorthwindImporter($pgReader);

        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        File::delete($path);
    }
});

test('northwind importer returns failure when executeSqlDump throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->andThrow(new RuntimeException('boom'));

    $manifest = require database_path('sources/northwind.php');
    $path = sourceFilePath('northwind', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "SELECT 1;\n");

    try {
        $importer = new NorthwindImporter($pgReader);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('boom');
    } finally {
        File::delete($path);
    }
});

test('northwind importer handles a missing manifest gracefully', function () {
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/northwind.php'));

    $importer = app(NorthwindImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer processes source rows when schema and data files exist', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')->twice();

    $manifest = require database_path('sources/pagila.php');
    $schemaPath = sourceFilePath('pagila', $manifest['schema_filename']);
    $dataPath = sourceFilePath('pagila', $manifest['data_filename']);
    File::ensureDirectoryExists(dirname($schemaPath));
    File::put($schemaPath, "SELECT 1;\n");
    File::put($dataPath, "SELECT 2;\n");

    try {
        $importer = new PagilaImporter($pgReader);

        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        File::delete($schemaPath);
        File::delete($dataPath);
    }
});

test('pagila importer returns failure when executeSqlDump throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->andThrow(new RuntimeException('pagila boom'));

    $manifest = require database_path('sources/pagila.php');
    $schemaPath = sourceFilePath('pagila', $manifest['schema_filename']);
    File::ensureDirectoryExists(dirname($schemaPath));
    File::put($schemaPath, "SELECT 1;\n");

    try {
        $importer = new PagilaImporter($pgReader);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse();
    } finally {
        File::delete($schemaPath);
    }
});

test('pagila importer handles a missing manifest gracefully', function () {
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/pagila.php'));

    $importer = app(PagilaImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer getSourceFilePath returns null when manifest is missing', function () {
    $pgReader = new PostgresSourceReader;
    $importer = new PagilaImporter($pgReader);

    File::shouldReceive('exists')
        ->andReturnFalse();

    $method = new ReflectionMethod($importer, 'getSourceFilePath');
    $method->setAccessible(true);

    $result = $method->invoke($importer, 'schema.sql');

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// PostgresSourceReader
// ---------------------------------------------------------------------------

test('postgres source reader throws when sql file does not exist', function () {
    $reader = new PostgresSourceReader;

    $reader->executeSqlDump('/nonexistent/path/file.sql', 'test_schema');
})->throws(RuntimeException::class, 'SQL file not found');

test('postgres source reader executes valid sql statements without throwing', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_valid_'.uniqid('', true).'.sql';
    File::put($tempFile, "SELECT 1;\nSELECT 2;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_schema');

        expect(File::exists($tempFile))->toBeTrue();
    } finally {
        File::delete($tempFile);
    }
});

test('postgres source reader catches errors on invalid sql and continues', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_invalid_'.uniqid('', true).'.sql';
    File::put($tempFile, "THIS IS NOT VALID SQL;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_schema');

        expect(File::exists($tempFile))->toBeTrue();
    } finally {
        File::delete($tempFile);
    }
});

test('postgres source reader strips psql meta-commands create database and set statements', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_meta_'.uniqid('', true).'.sql';
    File::put($tempFile, "\\c chinook_db\nCREATE DATABASE test_db;\nDROP DATABASE IF EXISTS test_db;\nSET client_encoding TO 'UTF8';\nSELECT 42;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_schema');

        expect(File::exists($tempFile))->toBeTrue();
    } finally {
        File::delete($tempFile);
    }
});

test('postgres source reader skips statements matching exclude patterns', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_exclude_'.uniqid('', true).'.sql';
    File::put($tempFile, "SELECT 1;\nSELECT 2;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_schema', ['/SELECT\s+2/i']);

        expect(File::exists($tempFile))->toBeTrue();
    } finally {
        File::delete($tempFile);
    }
});

test('postgres source reader rewrites public schema references to target schema', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_rewrite_'.uniqid('', true).'.sql';
    File::put($tempFile, "SELECT count(*) FROM public.nonexistent_table;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_target_schema');

        expect(File::exists($tempFile))->toBeTrue();
    } finally {
        File::delete($tempFile);
    }
});

test('postgres source reader executeMultiFile processes multiple files sequentially', function () {
    $fileA = sys_get_temp_dir().'/pest_multi_a_'.uniqid('', true).'.sql';
    $fileB = sys_get_temp_dir().'/pest_multi_b_'.uniqid('', true).'.sql';
    File::put($fileA, "SELECT 1;\n");
    File::put($fileB, "SELECT 2;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeMultiFile([$fileA, $fileB], 'pest_schema');

        expect(File::exists($fileA))->toBeTrue()
            ->and(File::exists($fileB))->toBeTrue();
    } finally {
        File::delete($fileA);
        File::delete($fileB);
    }
});

// ---------------------------------------------------------------------------
// SqlSourceReader
// ---------------------------------------------------------------------------

test('sql source reader getContent returns raw file content', function () {
    $tempFile = sys_get_temp_dir().'/pest_sql_'.uniqid('', true).'.sql';
    File::put($tempFile, "SELECT 1;\nSELECT 2;\n");

    try {
        $reader = new SqlSourceReader;

        expect($reader->getContent($tempFile))->toBe("SELECT 1;\nSELECT 2;\n");
    } finally {
        File::delete($tempFile);
    }
});

test('sql source reader getContent throws when file does not exist', function () {
    $reader = new SqlSourceReader;

    $reader->getContent('/nonexistent/file.sql');
})->throws(RuntimeException::class, 'SQL source file not found');

test('sql source reader getStatements splits and filters statements', function () {
    $tempFile = sys_get_temp_dir().'/pest_sql_stmt_'.uniqid('', true).'.sql';
    File::put($tempFile, "CREATE TABLE a (id int);\n\nINSERT INTO a VALUES (1);\n");

    try {
        $reader = new SqlSourceReader;
        $statements = $reader->getStatements($tempFile);

        expect($statements)->toHaveCount(2)
            ->and($statements[0])->toBe('CREATE TABLE a (id int)')
            ->and($statements[1])->toBe('INSERT INTO a VALUES (1)');
    } finally {
        File::delete($tempFile);
    }
});

test('sql source reader getStatements returns empty array for file with only comments', function () {
    $tempFile = sys_get_temp_dir().'/pest_sql_empty_'.uniqid('', true).'.sql';
    File::put($tempFile, "-- just a comment\n-- another one\n");

    try {
        $reader = new SqlSourceReader;

        expect($reader->getStatements($tempFile))->toBe([]);
    } finally {
        File::delete($tempFile);
    }
});

// ---------------------------------------------------------------------------
// SqliteSourceReader
// ---------------------------------------------------------------------------

function createTempSqliteFile(): string
{
    $path = sys_get_temp_dir().'/pest_sqlite_'.uniqid('', true).'.db';
    $pdo = new PDO("sqlite:{$path}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    $pdo->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY, total REAL)');
    $pdo->exec("INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com')");
    $pdo->exec("INSERT INTO users (name, email) VALUES ('Bob', 'bob@example.com')");
    $pdo->exec('INSERT INTO orders (total) VALUES (99.99)');
    $pdo = null;

    return $path;
}

test('sqlite source reader connect returns configured pdo instance', function () {
    $path = createTempSqliteFile();

    try {
        $reader = new SqliteSourceReader;
        $pdo = $reader->connect($path);

        expect($pdo->getAttribute(PDO::ATTR_ERRMODE))->toBe(PDO::ERRMODE_EXCEPTION)
            ->and($pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE))->toBe(PDO::FETCH_ASSOC);
    } finally {
        File::delete($path);
    }
});

test('sqlite source reader connect throws when file does not exist', function () {
    $reader = new SqliteSourceReader;

    $reader->connect('/nonexistent/file.db');
})->throws(RuntimeException::class, 'SQLite file not found');

test('sqlite source reader getTables returns user table names excluding sqlite internal tables', function () {
    $path = createTempSqliteFile();

    try {
        $reader = new SqliteSourceReader;
        $tables = $reader->getTables($path);

        expect($tables)->toContain('users')
            ->and($tables)->toContain('orders')
            ->and($tables)->not->toContain('sqlite_sequence');
    } finally {
        File::delete($path);
    }
});

test('sqlite source reader readTable returns all rows with associative keys', function () {
    $path = createTempSqliteFile();

    try {
        $reader = new SqliteSourceReader;
        $rows = $reader->readTable($path, 'users');

        expect($rows)->toHaveCount(2)
            ->and($rows[0])->toHaveKey('name')
            ->and($rows[0]['name'])->toBe('Alice')
            ->and($rows[1]['name'])->toBe('Bob');
    } finally {
        File::delete($path);
    }
});
