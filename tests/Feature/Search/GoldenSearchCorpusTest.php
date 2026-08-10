<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\PortfolioViewRecreator;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

covers(App\Services\Search\FederatedSearchService::class);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_source CASCADE');
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
});

/**
 * Golden Search Corpus: verifies search projection correctness
 * after a full source → staging → transform → publish lifecycle.
 */
test('golden search corpus: projections are correct after full chinook lifecycle', function () {
    // Build source and load fixture
    SourceSchemaBuilder::buildChinook();
    $sql = File::get(base_path('tests/Fixtures/Sources/chinook/minimal.sql'));
    $lines = explode("\n", $sql);
    $codeLines = array_filter($lines, fn (string $line): bool => ! str_starts_with(mb_trim($line), '--'));
    $cleanSql = implode("\n", $codeLines);
    foreach (array_filter(array_map('trim', explode(';', $cleanSql)), fn (string $statement): bool => $statement !== '') as $statement) {
        if ($statement !== '') {
            DB::statement($statement);
        }
    }

    // Build staging and transform
    app(StagingSchemaBuilder::class)->build('chinook');
    $mapper = new ChinookProductMapper;
    $mapper->load('chinook_source', 'chinook_staging');

    // Verify projections exist for each entity type
    $entityTypes = DB::table('chinook_staging.search_projections')->distinct()->pluck('entity_type')->all();
    expect($entityTypes)->toContain('artist')
        ->and($entityTypes)->toContain('album')
        ->and($entityTypes)->toContain('track');

    // Verify artist projection text
    $acdc = DB::table('chinook_staging.search_projections')
        ->where('entity_type', 'artist')
        ->where('weight_d_text', 'AC/DC')
        ->first();
    expect($acdc)->not->toBeNull();

    // Verify track projection text
    $track = DB::table('chinook_staging.search_projections')
        ->where('entity_type', 'track')
        ->where('weight_d_text', 'For Those About To Rock (We Salute You)')
        ->first();
    expect($track)->not->toBeNull()
        ->and($track->embedding_state)->toBe('pending');

    // Verify document_tsv is generated
    $tsvResult = DB::selectOne("SELECT document_tsv IS NOT NULL AS has_tsv FROM chinook_staging.search_projections WHERE entity_type = 'artist' LIMIT 1");
    expect($tsvResult->has_tsv)->toBeTrue();
});

test('golden search corpus: lexical search finds artist by name', function () {
    // Insert test data directly
    DB::table('chinook.search_projections')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'entity_type' => 'artist',
        'weight_d_text' => 'AC/DC',
        'embedding_state' => 'lexical_only',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('chinook.search_projections')->insert([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'entity_type' => 'artist',
        'weight_d_text' => 'Accept',
        'embedding_state' => 'lexical_only',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Lexical search via tsvector
    $results = DB::select("SELECT * FROM chinook.search_projections WHERE document_tsv @@ plainto_tsquery('en_unaccent', 'AC/DC')");

    expect($results)->not->toBeEmpty();
    expect(collect($results)->pluck('weight_d_text'))->toContain('AC/DC');
});

test('golden search corpus: projections survive schema swap', function () {
    // Build staging with data
    SourceSchemaBuilder::buildChinook();
    $sql = File::get(base_path('tests/Fixtures/Sources/chinook/minimal.sql'));
    $lines = explode("\n", $sql);
    $codeLines = array_filter($lines, fn (string $line): bool => ! str_starts_with(mb_trim($line), '--'));
    $cleanSql = implode("\n", $codeLines);
    foreach (array_filter(array_map('trim', explode(';', $cleanSql)), fn (string $statement): bool => $statement !== '') as $statement) {
        if ($statement !== '') {
            DB::statement($statement);
        }
    }

    app(StagingSchemaBuilder::class)->build('chinook');
    $mapper = new ChinookProductMapper;
    $mapper->load('chinook_source', 'chinook_staging');

    $stagingCount = DB::table('chinook_staging.search_projections')->count();
    expect($stagingCount)->toBeGreaterThan(0);

    // Publish: swap staging → live
    DB::transaction(function () {
        DB::statement('DROP SCHEMA IF EXISTS chinook CASCADE');
        DB::statement('ALTER SCHEMA chinook_staging RENAME TO chinook');
        app(PortfolioViewRecreator::class)->recreate();
    });

    // Verify projections survived the swap
    $liveCount = DB::table('chinook.search_projections')->count();
    expect($liveCount)->toBe($stagingCount);

    // Verify search still works on the published schema
    $results = DB::select("SELECT * FROM chinook.search_projections WHERE document_tsv @@ plainto_tsquery('en_unaccent', 'rock')");
    expect($results)->not->toBeEmpty();
});
