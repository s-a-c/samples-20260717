<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

covers(App\Console\Commands\SourceFetch::class);

test('all three pin manifests exist and return valid configuration arrays', function (string $product) {
    $manifestPath = database_path("sources/{$product}.php");

    expect(File::exists($manifestPath))->toBeTrue();

    /** @var array $manifest */
    $manifest = require $manifestPath;

    expect($manifest)->toBeArray()
        ->and($manifest)->toHaveKey('product')
        ->and($manifest)->toHaveKey('repository')
        ->and($manifest)->toHaveKey('commit_sha')
        ->and($manifest)->toHaveKey('format')
        ->and($manifest['product'])->toBe($product)
        ->and($manifest['repository'])->toBeString()->not->toBeEmpty()
        ->and($manifest['commit_sha'])->toBeString()->not->toBeEmpty()
        ->and($manifest['format'])->toBeIn(['sql_dump', 'sqlite_binary', 'postgresql_sql', 'postgresql_multi']);

    if ($manifest['format'] === 'postgresql_multi') {
        expect($manifest)->toHaveKeys(['schema_filename', 'data_filename', 'schema_digest', 'data_digest'])
            ->and($manifest['schema_filename'])->toBeString()->not->toBeEmpty()
            ->and($manifest['data_filename'])->toBeString()->not->toBeEmpty()
            ->and($manifest['schema_digest'])->toBeString()->toHaveLength(64)
            ->and($manifest['data_digest'])->toBeString()->toHaveLength(64);
    } else {
        expect($manifest)->toHaveKeys(['filename', 'digest'])
            ->and($manifest['filename'])->toBeString()->not->toBeEmpty()
            ->and($manifest['digest'])->toBeString()->toHaveLength(64);
    }
})->with(['chinook', 'northwind', 'pagila']);

test('source:fetch fails for invalid product', function () {
    $this->artisan('source:fetch', ['product' => 'invalid_product'])
        ->assertFailed()
        ->expectsOutput("Manifest for product 'invalid_product' not found.");
});

test('source:fetch validates digest, fetches file to private storage, and skips when cached', function () {
    $testContent = 'hello dataset content for unit testing';
    $digest = hash('sha256', $testContent);

    $testManifestPath = database_path('sources/test_dummy.php');
    File::put($testManifestPath, "<?php return [
        'product' => 'test_dummy',
        'repository' => 'test/repo',
        'commit_sha' => 'abc1234',
        'filename' => 'data.db',
        'digest' => '{$digest}',
        'format' => 'sqlite_binary',
    ];");

    $targetFile = storage_path('app/private/sources/test_dummy/abc1234/data.db');
    if (File::exists($targetFile)) {
        File::delete($targetFile);
    }

    try {
        Http::fake([
            'https://raw.githubusercontent.com/*' => Http::sequence()
                ->push(null, 500)
                ->push('corrupted content', 200)
                ->push($testContent, 200),
        ]);

        // 1. HTTP download failure
        $this->artisan('source:fetch', ['product' => 'test_dummy'])
            ->assertFailed()
            ->expectsOutput('Failed to download file from https://raw.githubusercontent.com/test/repo/abc1234/data.db');

        // 2. Digest mismatch failure
        $corruptedDigest = hash('sha256', 'corrupted content');
        $this->artisan('source:fetch', ['product' => 'test_dummy'])
            ->assertFailed()
            ->expectsOutput("Digest mismatch! Expected: {$digest}, Got: {$corruptedDigest}");

        expect(File::exists($targetFile))->toBeFalse();

        // 3. Successful download and digest verification
        $this->artisan('source:fetch', ['product' => 'test_dummy'])
            ->assertSuccessful()
            ->expectsOutput("Dataset 'test_dummy' fetched and digest verified successfully.");

        expect(File::exists($targetFile))->toBeTrue()
            ->and(File::get($targetFile))->toBe($testContent)
            ->and(hash_file('sha256', $targetFile))->toBe($digest);

        // 4. Skip download when already present and verified
        $this->artisan('source:fetch', ['product' => 'test_dummy'])
            ->assertSuccessful()
            ->expectsOutput("Dataset 'test_dummy' already fetched and verified.");
    } finally {
        if (File::exists($testManifestPath)) {
            File::delete($testManifestPath);
        }
        if (File::exists($targetFile)) {
            File::delete($targetFile);
        }
        if (File::isDirectory(storage_path('app/private/sources/test_dummy'))) {
            File::deleteDirectory(storage_path('app/private/sources/test_dummy'));
        }
    }
});

test('source:fetch multi-file fetches schema and data files for postgresql_multi format', function () {
    $schemaContent = 'CREATE TABLE foo (id int);';
    $dataContent = 'INSERT INTO foo VALUES (1);';
    $schemaDigest = hash('sha256', $schemaContent);
    $dataDigest = hash('sha256', $dataContent);

    $testManifestPath = database_path('sources/test_multi.php');
    File::put($testManifestPath, "<?php return [
        'product' => 'test_multi',
        'repository' => 'test/multirepo',
        'commit_sha' => 'def5678',
        'schema_filename' => 'schema.sql',
        'data_filename' => 'data.sql',
        'schema_digest' => '{$schemaDigest}',
        'data_digest' => '{$dataDigest}',
        'format' => 'postgresql_multi',
    ];");

    $schemaFile = storage_path('app/private/sources/test_multi/def5678/schema.sql');
    $dataFile = storage_path('app/private/sources/test_multi/def5678/data.sql');

    try {
        Http::fake([
            'https://raw.githubusercontent.com/test/multirepo/def5678/schema.sql' => Http::response($schemaContent, 200),
            'https://raw.githubusercontent.com/test/multirepo/def5678/data.sql' => Http::response($dataContent, 200),
        ]);

        $this->artisan('source:fetch', ['product' => 'test_multi'])
            ->assertSuccessful()
            ->expectsOutput("Dataset file 'schema.sql' fetched and digest verified successfully.")
            ->expectsOutput("Dataset file 'data.sql' fetched and digest verified successfully.");

        expect(File::exists($schemaFile))->toBeTrue()
            ->and(File::exists($dataFile))->toBeTrue()
            ->and(File::get($schemaFile))->toBe($schemaContent)
            ->and(File::get($dataFile))->toBe($dataContent);

        // Second run skips both files
        $this->artisan('source:fetch', ['product' => 'test_multi'])
            ->assertSuccessful()
            ->expectsOutput("Dataset file 'schema.sql' already fetched and verified.")
            ->expectsOutput("Dataset file 'data.sql' already fetched and verified.");
    } finally {
        if (File::exists($testManifestPath)) {
            File::delete($testManifestPath);
        }
        if (File::isDirectory(storage_path('app/private/sources/test_multi'))) {
            File::deleteDirectory(storage_path('app/private/sources/test_multi'));
        }
    }
});

test('source:fetch multi-file fails on digest mismatch', function () {
    $schemaContent = 'valid schema';
    $schemaDigest = hash('sha256', $schemaContent);
    $badDataDigest = str_repeat('0', 64);

    $testManifestPath = database_path('sources/test_multi_bad.php');
    File::put($testManifestPath, "<?php return [
        'product' => 'test_multi_bad',
        'repository' => 'test/multirepo',
        'commit_sha' => 'ghi9012',
        'schema_filename' => 'schema.sql',
        'data_filename' => 'data.sql',
        'schema_digest' => '{$schemaDigest}',
        'data_digest' => '{$badDataDigest}',
        'format' => 'postgresql_multi',
    ];");

    try {
        Http::fake([
            'https://raw.githubusercontent.com/test/multirepo/ghi9012/schema.sql' => Http::response($schemaContent, 200),
            'https://raw.githubusercontent.com/test/multirepo/ghi9012/data.sql' => Http::response('unexpected', 200),
        ]);

        $this->artisan('source:fetch', ['product' => 'test_multi_bad'])
            ->assertFailed();
    } finally {
        if (File::exists($testManifestPath)) {
            File::delete($testManifestPath);
        }
        if (File::isDirectory(storage_path('app/private/sources/test_multi_bad'))) {
            File::deleteDirectory(storage_path('app/private/sources/test_multi_bad'));
        }
    }
});
