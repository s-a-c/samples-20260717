<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\Chinook\ChinookProductMapper;
use App\Services\ProductImport\Schema\SourceSchemaBuilder;
use App\Services\ProductImport\StagingSchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

covers(
    ChinookProductMapper::class,
    App\Services\ProductImport\Mapping\Chinook\ArtistMapper::class,
    App\Services\ProductImport\Mapping\Chinook\AlbumMapper::class,
    App\Services\ProductImport\Mapping\Chinook\GenreMapper::class,
    App\Services\ProductImport\Mapping\Chinook\MediaTypeMapper::class,
    App\Services\ProductImport\Mapping\Chinook\EmployeeMapper::class,
    App\Services\ProductImport\Mapping\Chinook\CustomerMapper::class,
    App\Services\ProductImport\Mapping\Chinook\TrackMapper::class,
    App\Services\ProductImport\Mapping\Chinook\PlaylistMapper::class,
    App\Services\ProductImport\Mapping\Chinook\InvoiceMapper::class,
    SourceSchemaBuilder::class,
);

uses(RefreshDatabase::class);

afterEach(function () {
    DB::statement('DROP SCHEMA IF EXISTS chinook_source CASCADE');
    DB::statement('DROP SCHEMA IF EXISTS chinook_staging CASCADE');
});

function loadChinookFixture(): void
{
    SourceSchemaBuilder::buildChinook();

    $sql = File::get(base_path('tests/Fixtures/Sources/chinook/minimal.sql'));

    // Strip comment lines
    $lines = explode("\n", $sql);
    $codeLines = array_filter($lines, fn (string $line): bool => ! str_starts_with(mb_trim($line), '--'));
    $cleanSql = implode("\n", $codeLines);

    // Execute each statement
    foreach (array_filter(
        array_map('trim', explode(';', $cleanSql)),
        fn (string $statement): bool => $statement !== '',
    ) as $statement) {
        if ($statement !== '') {
            DB::statement($statement);
        }
    }
}

beforeEach(function () {
    loadChinookFixture();
    app(StagingSchemaBuilder::class)->build('chinook');
});

test('chinook transform loads all artists with UUID PKs', function () {
    $mapper = new ChinookProductMapper;

    $result = $mapper->load('chinook_source', 'chinook_staging');

    expect($result['rows'])->toBeGreaterThan(0);

    $artists = DB::table('chinook_staging.artists')->get();
    expect($artists)->toHaveCount(2);

    foreach ($artists as $artist) {
        expect($artist->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    }
});

test('chinook transform resolves album artist FK', function () {
    $mapper = new ChinookProductMapper;

    $mapper->load('chinook_source', 'chinook_staging');

    $albums = DB::table('chinook_staging.albums')->get();
    expect($albums)->toHaveCount(2);

    // Each album's artist_id must exist in staging artists
    $artistIds = DB::table('chinook_staging.artists')->pluck('id')->all();
    foreach ($albums as $album) {
        expect($artistIds)->toContain($album->artist_id);
    }
});

test('chinook transform resolves employee self-referential FK', function () {
    $mapper = new ChinookProductMapper;

    $mapper->load('chinook_source', 'chinook_staging');

    $employees = DB::table('chinook_staging.employees')->orderBy('last_name')->get();
    expect($employees)->toHaveCount(2);

    // Andrew Adams (EmployeeId 1) has reports_to = NULL
    // Nancy Edwards (EmployeeId 2) has reports_to = 1 (Andrew)
    $andrew = $employees->firstWhere('last_name', 'Adams');
    $nancy = $employees->firstWhere('last_name', 'Edwards');

    expect($andrew?->reports_to)->toBeNull();
    expect($nancy?->reports_to)
        ->not
        ->toBeNull()
        ->and($nancy->reports_to)
        ->toBe($andrew?->id);
});

test('chinook transform resolves track FKs', function () {
    $mapper = new ChinookProductMapper;

    $mapper->load('chinook_source', 'chinook_staging');

    $tracks = DB::table('chinook_staging.tracks')->get();
    expect($tracks)->toHaveCount(2);

    $albumIds = DB::table('chinook_staging.albums')->pluck('id')->all();
    $mediaTypeIds = DB::table('chinook_staging.media_types')->pluck('id')->all();

    foreach ($tracks as $track) {
        expect($albumIds)->toContain($track->album_id)->and($mediaTypeIds)->toContain($track->media_type_id);
    }
});

test('chinook transform creates search projection rows', function () {
    $mapper = new ChinookProductMapper;

    $mapper->load('chinook_source', 'chinook_staging');

    // Artists should have projections
    $artistProjections = DB::table('chinook_staging.search_projections')
        ->where('entity_type', 'artist')
        ->get();
    expect($artistProjections)->toHaveCount(2);

    // Check the projection text contains the artist name
    expect($artistProjections->first()?->weight_d_text)->toBe('AC/DC');
});

test('chinook transform preserves source identity across re-imports', function () {
    $mapper = new ChinookProductMapper;

    // First load
    $mapper->load('chinook_source', 'chinook_staging');
    $firstArtistId = DB::table('chinook_staging.artists')->first()?->id;

    // Rebuild staging schema from scratch
    app(StagingSchemaBuilder::class)->build('chinook');
    $mapper->load('chinook_source', 'chinook_staging');
    $secondArtistId = DB::table('chinook_staging.artists')->first()?->id;

    // Source identity registry should return the same UUID
    expect($secondArtistId)->toBe($firstArtistId);
});

test('chinook transform loads invoices with items', function () {
    $mapper = new ChinookProductMapper;

    $mapper->load('chinook_source', 'chinook_staging');

    $invoices = DB::table('chinook_staging.invoices')->get();
    expect($invoices)->toHaveCount(1);

    $invoiceLines = DB::table('chinook_staging.invoice_lines')->get();
    expect($invoiceLines)->toHaveCount(2);
});

test('chinook mapper exposes its ordered table mappers', function () {
    $method = new ReflectionMethod(ChinookProductMapper::class, 'mappers');
    $mappers = $method->invoke(new ChinookProductMapper);

    expect($mappers)->toHaveCount(9);
});

test('source schema builder rejects an unknown product', function () {
    SourceSchemaBuilder::create('unknown');
})->throws(InvalidArgumentException::class, 'Unknown product: unknown');

test('chinook transform preserves nullable track foreign keys', function () {
    DB::table('chinook_source.track')->insert([
        'track_id' => 99,
        'name' => 'No Album Track',
        'album_id' => null,
        'media_type_id' => 1,
        'genre_id' => null,
        'composer' => null,
        'milliseconds' => 100,
        'bytes' => null,
        'unit_price' => 0.99,
    ]);

    new ChinookProductMapper()->load('chinook_source', 'chinook_staging');

    $track = DB::table('chinook_staging.tracks')->where('name', 'No Album Track')->first();
    expect($track?->album_id)->toBeNull()->and($track?->genre_id)->toBeNull();
});
