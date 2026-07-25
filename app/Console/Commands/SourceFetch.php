<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SourceFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'source:fetch {product : chinook|northwind|pagila}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and verify dataset source file from raw GitHub for specified product';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $product = strtolower($this->argument('product'));
        $manifestPath = database_path("sources/{$product}.php");

        if (! File::exists($manifestPath)) {
            $this->error("Manifest for product '{$product}' not found.");

            return self::FAILURE;
        }

        /** @var array{product: string, repository: string, commit_sha: string, format: string, filename?: string, schema_filename?: string, data_filename?: string, digest?: string, schema_digest?: string, data_digest?: string} $manifest */
        $manifest = require $manifestPath;

        if ($manifest['format'] === 'postgresql_multi') {
            return $this->fetchMultiFile($manifest);
        }

        return $this->fetchSingleFile($manifest);
    }

    /**
     * Fetch a single-file source.
     *
     * @param  array{product: string, repository: string, commit_sha: string, filename: string, digest: string}  $manifest
     */
    protected function fetchSingleFile(array $manifest): int
    {
        $targetFile = storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$manifest['filename']}");
        File::ensureDirectoryExists(dirname($targetFile));

        if (File::exists($targetFile) && hash_file('sha256', $targetFile) === $manifest['digest']) {
            $this->info("Dataset '{$manifest['product']}' already fetched and verified.");

            return self::SUCCESS;
        }

        $rawUrl = "https://raw.githubusercontent.com/{$manifest['repository']}/{$manifest['commit_sha']}/{$manifest['filename']}";
        $this->info("Fetching dataset from: {$rawUrl}");

        $response = Http::get($rawUrl);

        if (! $response->successful()) {
            $this->error("Failed to download file from {$rawUrl}");

            return self::FAILURE;
        }

        File::put($targetFile, $response->body());

        $computedDigest = hash_file('sha256', $targetFile);

        if ($computedDigest !== $manifest['digest']) {
            $this->error("Digest mismatch! Expected: {$manifest['digest']}, Got: {$computedDigest}");
            File::delete($targetFile);

            return self::FAILURE;
        }

        $this->info("Dataset '{$manifest['product']}' fetched and digest verified successfully.");

        return self::SUCCESS;
    }

    /**
     * Fetch a multi-file source (schema + data).
     *
     * @param  array{product: string, repository: string, commit_sha: string, schema_filename: string, data_filename: string, schema_digest: string, data_digest: string}  $manifest
     */
    protected function fetchMultiFile(array $manifest): int
    {
        $files = [
            ['filename' => $manifest['schema_filename'], 'digest' => $manifest['schema_digest']],
            ['filename' => $manifest['data_filename'], 'digest' => $manifest['data_digest']],
        ];

        foreach ($files as $file) {
            $targetFile = storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}/{$file['filename']}");
            File::ensureDirectoryExists(dirname($targetFile));

            if (File::exists($targetFile) && hash_file('sha256', $targetFile) === $file['digest']) {
                $this->info("Dataset file '{$file['filename']}' already fetched and verified.");

                continue;
            }

            $rawUrl = "https://raw.githubusercontent.com/{$manifest['repository']}/{$manifest['commit_sha']}/{$file['filename']}";
            $this->info("Fetching dataset file from: {$rawUrl}");

            $response = Http::get($rawUrl);

            if (! $response->successful()) {
                $this->error("Failed to download file from {$rawUrl}");

                return self::FAILURE;
            }

            File::put($targetFile, $response->body());

            $computedDigest = hash_file('sha256', $targetFile);

            if ($computedDigest !== $file['digest']) {
                $this->error("Digest mismatch for '{$file['filename']}'! Expected: {$file['digest']}, Got: {$computedDigest}");
                File::delete($targetFile);

                return self::FAILURE;
            }

            $this->info("Dataset file '{$file['filename']}' fetched and digest verified successfully.");
        }

        return self::SUCCESS;
    }
}
