<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

test('all three pin manifests exist and return valid configuration arrays', function (string $product) {
    $manifestPath = database_path("sources/{$product}.php");

    expect(File::exists($manifestPath))->toBeTrue();

    /** @var array $manifest */
    $manifest = require $manifestPath;

    expect($manifest)->toBeArray()
        ->and($manifest)->toHaveKeys(['product', 'repository', 'commit_sha', 'filename', 'digest', 'format'])
        ->and($manifest['product'])->toBe($product)
        ->and($manifest['repository'])->toBeString()->not->toBeEmpty()
        ->and($manifest['commit_sha'])->toBeString()->not->toBeEmpty()
        ->and($manifest['filename'])->toBeString()->not->toBeEmpty()
        ->and($manifest['digest'])->toBeString()->toHaveLength(64)
        ->and($manifest['format'])->toBeIn(['sql_dump', 'sqlite_binary']);
})->with(['chinook', 'northwind', 'sakila']);

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
