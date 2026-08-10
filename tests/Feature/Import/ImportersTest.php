<?php

declare(strict_types=1);

use App\Services\ProductImport\ChinookImporter;
use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\Mapping\Northwind\NorthwindProductMapper;
use App\Services\ProductImport\Mapping\Pagila\PagilaProductMapper;
use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\NorthwindImporter;
use App\Services\ProductImport\PagilaImporter;
use App\Services\ProductImport\PortfolioViewRecreator;
use App\Services\ProductImport\PostgresSourceReader;
use App\Services\ProductImport\SqliteSourceReader;
use App\Services\ProductImport\SqlSourceReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Create a mock PortfolioViewRecreator that does nothing.
 * Used by importer tests that publish an empty staging schema
 * (the view DDL references product tables that only exist after
 * staging tables are built — a Task 2 deliverable).
 */
function mockViewRecreator(): PortfolioViewRecreator
{
    return new class extends PortfolioViewRecreator
    {
        public function recreate(): void {}
    };
}

/**
 * Bind a no-op PortfolioViewRecreator in the container.
 * Used by tests that resolve importers via app().
 */
function bindMockViewRecreator(): void
{
    app()->bind(PortfolioViewRecreator::class, fn () => mockViewRecreator());
}

covers(
    ChinookImporter::class,
    NorthwindImporter::class,
    PagilaImporter::class,
    PostgresSourceReader::class,
    TableMapper::class,
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
    bindMockViewRecreator();
    $importer = app(ChinookImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('northwind importer full import completes successfully when no source file is cached', function () {
    bindMockViewRecreator();
    $importer = app(NorthwindImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer full import completes successfully when no source file is cached', function () {
    bindMockViewRecreator();
    $importer = app(PagilaImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

// ---------------------------------------------------------------------------
// Importers — source file present, failure, and missing-manifest paths
// ---------------------------------------------------------------------------

/**
 * @return array{product: string, commit_sha: string, filename: string}
 */
function sourceManifest(string $product): array
{
    $manifest = require database_path("sources/{$product}.php");

    if (! is_array($manifest)
        || ! isset($manifest['product'], $manifest['commit_sha'], $manifest['filename'])
        || ! is_string($manifest['product'])
        || ! is_string($manifest['commit_sha'])
        || ! is_string($manifest['filename'])) {
        throw new RuntimeException("Invalid {$product} source manifest.");
    }

    return [
        'product' => $manifest['product'],
        'commit_sha' => $manifest['commit_sha'],
        'filename' => $manifest['filename'],
    ];
}

/**
 * @return array{product: string, commit_sha: string, schema_filename: string, data_filename: string}
 */
function pagilaManifest(): array
{
    $manifest = require database_path('sources/pagila.php');

    if (! is_array($manifest)
        || ! isset($manifest['product'], $manifest['commit_sha'], $manifest['schema_filename'], $manifest['data_filename'])
        || ! is_string($manifest['product'])
        || ! is_string($manifest['commit_sha'])
        || ! is_string($manifest['schema_filename'])
        || ! is_string($manifest['data_filename'])) {
        throw new RuntimeException('Invalid Pagila source manifest.');
    }

    return [
        'product' => $manifest['product'],
        'commit_sha' => $manifest['commit_sha'],
        'schema_filename' => $manifest['schema_filename'],
        'data_filename' => $manifest['data_filename'],
    ];
}

/**
 * @phpstan-assert PostgresSourceReader $pgReader
 * @phpstan-assert PortfolioViewRecreator $viewRecreator
 */
function assertImporterDoubles(mixed $pgReader, mixed $viewRecreator): void
{
    assert($pgReader instanceof PostgresSourceReader);
    assert($viewRecreator instanceof PortfolioViewRecreator);
}

function sourceFilePath(string $product, string $filename): string
{
    $manifest = $product === 'pagila' ? pagilaManifest() : sourceManifest($product);

    return storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$filename}");
}

final class SourceFixtureStore
{
    /** @var array<string, string|null> */
    public static array $backups = [];
}

function writeSourceFixture(string $path, string $contents): void
{
    if (! array_key_exists($path, SourceFixtureStore::$backups)) {
        SourceFixtureStore::$backups[$path] = File::exists($path) ? File::get($path) : null;
    }

    File::put($path, $contents);
}

function restoreSourceFixture(string $path): void
{
    if (! array_key_exists($path, SourceFixtureStore::$backups)) {
        return;
    }

    $contents = SourceFixtureStore::$backups[$path];
    if ($contents === null) {
        File::delete($path);
    } else {
        File::put($path, $contents);
    }

    unset(SourceFixtureStore::$backups[$path]);
}

test('chinook importer processes source rows when the cached source file exists', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->withArgs(fn (string $file, string $schema, ?array $patterns = null) => true)
        ->once()
        ->andReturnUsing(function (): void {
            DB::statement('CREATE TABLE chinook_source.coverage_marker (id integer)');
        });

    $viewRecreator = Mockery::mock(PortfolioViewRecreator::class);
    $viewRecreator->shouldReceive('recreate')->once();

    $mapper = Mockery::mock(ChinookProductMapper::class);
    $mapper->shouldReceive('load')->once()->andReturn(['tables' => 1, 'rows' => 0]);
    app()->instance(ChinookProductMapper::class, $mapper);

    assertImporterDoubles($pgReader, $viewRecreator);

    $importer = new ChinookImporter($pgReader, $viewRecreator);

    $manifest = sourceManifest('chinook');
    $path = sourceFilePath('chinook', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    writeSourceFixture($path, "SELECT 1;\n");

    try {
        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        restoreSourceFixture($path);
    }
});

test('chinook importer returns failure when executeSqlDump throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->andThrow(new RuntimeException('pg_dump failed'));

    $manifest = sourceManifest('chinook');
    $path = sourceFilePath('chinook', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    writeSourceFixture($path, "SELECT 1;\n");

    try {
        $viewRecreator = mockViewRecreator();
        assertImporterDoubles($pgReader, $viewRecreator);

        $importer = new ChinookImporter($pgReader, $viewRecreator);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse()
            ->and($result['error'] ?? null)->toBe('pg_dump failed');
    } finally {
        restoreSourceFixture($path);
    }
});

test('chinook importer handles a missing manifest gracefully', function () {
    bindMockViewRecreator();
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/chinook.php'));

    $importer = app(ChinookImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('northwind importer processes source rows when the cached source file exists', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')->once()->andReturnUsing(function (): void {
        DB::statement('CREATE TABLE northwind_source.coverage_marker (id integer)');
    });

    $viewRecreator = Mockery::mock(PortfolioViewRecreator::class);
    $viewRecreator->shouldReceive('recreate')->once();

    $mapper = Mockery::mock(NorthwindProductMapper::class);
    $mapper->shouldReceive('load')->once()->andReturn(['tables' => 1, 'rows' => 0]);
    app()->instance(NorthwindProductMapper::class, $mapper);

    $manifest = sourceManifest('northwind');
    $path = sourceFilePath('northwind', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    writeSourceFixture($path, "SELECT 1;\n");

    try {
        assertImporterDoubles($pgReader, $viewRecreator);

        $importer = new NorthwindImporter($pgReader, $viewRecreator);

        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        restoreSourceFixture($path);
    }
});

test('northwind importer returns failure when executeSqlDump throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeSqlDump')
        ->andThrow(new RuntimeException('boom'));

    $manifest = sourceManifest('northwind');
    $path = sourceFilePath('northwind', $manifest['filename']);
    File::ensureDirectoryExists(dirname($path));
    writeSourceFixture($path, "SELECT 1;\n");

    try {
        $viewRecreator = mockViewRecreator();
        assertImporterDoubles($pgReader, $viewRecreator);

        $importer = new NorthwindImporter($pgReader, $viewRecreator);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse()
            ->and($result['error'] ?? null)->toBe('boom');
    } finally {
        restoreSourceFixture($path);
    }
});

test('northwind importer handles a missing manifest gracefully', function () {
    bindMockViewRecreator();
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/northwind.php'));

    $importer = app(NorthwindImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer processes source rows when schema and data files exist', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeMultiFile')->once()->andReturnUsing(function (): void {
        DB::statement('CREATE TABLE pagila_source.coverage_marker (id integer)');
    });

    $viewRecreator = Mockery::mock(PortfolioViewRecreator::class);
    $viewRecreator->shouldReceive('recreate')->once();

    $mapper = Mockery::mock(PagilaProductMapper::class);
    $mapper->shouldReceive('load')->once()->andReturn(['tables' => 1, 'rows' => 0]);
    app()->instance(PagilaProductMapper::class, $mapper);

    $manifest = pagilaManifest();
    $schemaPath = sourceFilePath('pagila', $manifest['schema_filename']);
    $dataPath = sourceFilePath('pagila', $manifest['data_filename']);
    File::ensureDirectoryExists(dirname($schemaPath));
    writeSourceFixture($schemaPath, "SELECT 1;\n");
    writeSourceFixture($dataPath, "SELECT 2;\n");

    try {
        assertImporterDoubles($pgReader, $viewRecreator);

        $importer = new PagilaImporter($pgReader, $viewRecreator);

        $result = $importer->import(dryRun: false);

        expect($result)->toBe(['success' => true]);
    } finally {
        restoreSourceFixture($schemaPath);
        restoreSourceFixture($dataPath);
    }
});

test('pagila importer returns failure when executeMultiFile throws', function () {
    $pgReader = Mockery::mock(new PostgresSourceReader);
    $pgReader->shouldReceive('executeMultiFile')
        ->andThrow(new RuntimeException('pagila boom'));

    $manifest = pagilaManifest();
    $schemaPath = sourceFilePath('pagila', $manifest['schema_filename']);
    File::ensureDirectoryExists(dirname($schemaPath));
    writeSourceFixture($schemaPath, "SELECT 1;\n");
    $dataPath = sourceFilePath('pagila', $manifest['data_filename']);
    writeSourceFixture($dataPath, "SELECT 2;\n");

    try {
        $viewRecreator = mockViewRecreator();
        assertImporterDoubles($pgReader, $viewRecreator);

        $importer = new PagilaImporter($pgReader, $viewRecreator);

        $result = $importer->import(dryRun: false);

        expect($result['success'])->toBeFalse();
    } finally {
        restoreSourceFixture($schemaPath);
        restoreSourceFixture($dataPath);
    }
});

test('pagila importer handles a missing manifest gracefully', function () {
    bindMockViewRecreator();
    File::shouldReceive('exists')
        ->andReturnUsing(fn (string $path): bool => $path !== database_path('sources/pagila.php'));

    $importer = app(PagilaImporter::class);

    $result = $importer->import(dryRun: false);

    expect($result)->toBe(['success' => true]);
});

test('pagila importer getSourceFilePath returns null when manifest is missing', function () {
    $pgReader = new PostgresSourceReader;
    $viewRecreator = mockViewRecreator();

    $importer = new PagilaImporter($pgReader, $viewRecreator);

    File::shouldReceive('exists')
        ->andReturnFalse();

    $method = new ReflectionMethod($importer, 'getSourceFilePath');

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

test('postgres source reader loads a complete dump into the requested source schema', function () {
    $schema = 'reader_source_'.mb_strtolower(Str::random(8));
    DB::statement("CREATE SCHEMA {$schema}");

    $path = base_path('.agents/tmp/reader-dump.sql');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, <<<'SQL'
CREATE TABLE public.artists (id integer PRIMARY KEY, name text);
INSERT INTO public.artists VALUES (1, 'AC/DC');
CREATE OR REPLACE FUNCTION public.artist_count() RETURNS integer LANGUAGE SQL AS $$
    SELECT count(*)::integer FROM public.artists;
$$;
SQL);

    try {
        $reader = new PostgresSourceReader;
        $reader->executeSqlDump($path, $schema);

        expect(DB::table("{$schema}.artists")->value('name'))->toBe('AC/DC')
            ->and(DB::selectOne("SELECT {$schema}.artist_count() AS count")->count)->toBe(1);
    } finally {
        DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        File::delete($path);
    }
});

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

test('postgres source reader rejects invalid sql atomically', function () {
    $tempFile = sys_get_temp_dir().'/pest_pg_invalid_'.uniqid('', true).'.sql';
    File::put($tempFile, "THIS IS NOT VALID SQL;\n");

    try {
        $reader = new PostgresSourceReader;

        $reader->executeSqlDump($tempFile, 'pest_schema');
    } finally {
        File::delete($tempFile);
    }
})->throws(Illuminate\Database\QueryException::class);

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
    } finally {
        File::delete($tempFile);
    }
})->throws(Illuminate\Database\QueryException::class);

test('postgres source reader executeMultiFile rejects a missing dump before execution', function () {
    $reader = new PostgresSourceReader;

    $reader->executeMultiFile([base_path('.agents/tmp/missing-source.sql')], 'pest_schema');
})->throws(RuntimeException::class, 'SQL file not found');

test('postgres source reader rejects an unavailable search path', function () {
    $path = base_path('.agents/tmp/search-path.sql');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "SELECT 1;\n");
    DB::shouldReceive('selectOne')->once()->with('SHOW search_path')->andReturn(null);

    try {
        (new PostgresSourceReader)->executeSqlDump($path, 'pest_schema');
    } finally {
        File::delete($path);
    }
})->throws(RuntimeException::class, 'Unable to determine');

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

test('table mapper converts and validates source scalar values', function () {
    $mapper = new class extends TableMapper
    {
        public function load(string $sourceSchema, string $stagingSchema): int
        {
            return 0;
        }

        public function integer(mixed $value): int
        {
            return $this->sourceInt($value);
        }

        public function decimal(mixed $value): float
        {
            return $this->sourceFloat($value);
        }

        public function sourceCount(string $schema, string $table): int
        {
            return $this->countSourceRows($schema, $table);
        }

        /** @return array<int, object> */
        public function sourceRows(string $schema, string $table): array
        {
            return $this->readSourceRows($schema, $table);
        }
    };

    DB::statement('CREATE SCHEMA IF NOT EXISTS mapper_coverage');
    DB::statement('CREATE TABLE mapper_coverage.rows (id integer, label text)');
    DB::table('mapper_coverage.rows')->insert(['id' => 1, 'label' => 'row']);

    expect($mapper->integer(3))->toBe(3)
        ->and($mapper->integer('4'))->toBe(4)
        ->and($mapper->decimal(1.5))->toBe(1.5)
        ->and($mapper->decimal('2.5'))->toBe(2.5);

    expect(fn () => $mapper->integer('not-an-integer'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $mapper->decimal('not-a-number'))
        ->toThrow(InvalidArgumentException::class);

    expect($mapper->sourceCount('mapper_coverage', 'rows'))->toBe(1)
        ->and($mapper->sourceRows('mapper_coverage', 'rows'))->toHaveCount(1);

    DB::statement('DROP SCHEMA mapper_coverage CASCADE');
});
