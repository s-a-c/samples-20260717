<?php

declare(strict_types=1);

use App\Models\ResetRun;
use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/** @var array<string, array{filename: string, fixture: string}> */
const BEHAVIORAL_IMPORT_FIXTURES = [
    'chinook' => [
        'filename' => 'ChinookDatabase/DataSources/Chinook_PostgreSql.sql',
        'fixture' => 'tests/Fixtures/Sources/chinook/minimal.sql',
    ],
    'northwind' => [
        'filename' => 'northwind.sql',
        'fixture' => 'tests/Fixtures/Sources/northwind/minimal.sql',
    ],
    'pagila' => [
        'filename' => 'pagila-insert-data.sql',
        'fixture' => 'tests/Fixtures/Sources/pagila/minimal.sql',
    ],
];

const BEHAVIORAL_SOURCE_DDL = [
    'chinook' => <<<'SQL'
CREATE TABLE artist (artist_id INTEGER PRIMARY KEY, name TEXT);
CREATE TABLE album (album_id INTEGER PRIMARY KEY, title TEXT, artist_id INTEGER);
CREATE TABLE genre (genre_id INTEGER PRIMARY KEY, name TEXT);
CREATE TABLE media_type (media_type_id INTEGER PRIMARY KEY, name TEXT);
CREATE TABLE employee (employee_id INTEGER PRIMARY KEY, last_name TEXT, first_name TEXT, title TEXT, reports_to INTEGER, birth_date TEXT, hire_date TEXT, address TEXT, city TEXT, state TEXT, country TEXT, postal_code TEXT, phone TEXT, fax TEXT, email TEXT);
CREATE TABLE customer (customer_id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, company TEXT, address TEXT, city TEXT, state TEXT, country TEXT, postal_code TEXT, phone TEXT, fax TEXT, email TEXT, support_rep_id INTEGER);
CREATE TABLE track (track_id INTEGER PRIMARY KEY, name TEXT, album_id INTEGER, media_type_id INTEGER, genre_id INTEGER, composer TEXT, milliseconds INTEGER, bytes INTEGER, unit_price REAL);
CREATE TABLE playlist (playlist_id INTEGER PRIMARY KEY, name TEXT);
CREATE TABLE playlist_track (playlist_id INTEGER, track_id INTEGER);
CREATE TABLE invoice (invoice_id INTEGER PRIMARY KEY, customer_id INTEGER, invoice_date TEXT, billing_address TEXT, billing_city TEXT, billing_state TEXT, billing_country TEXT, billing_postal_code TEXT, total REAL);
CREATE TABLE invoice_line (invoice_line_id INTEGER PRIMARY KEY, invoice_id INTEGER, track_id INTEGER, unit_price REAL, quantity INTEGER);
SQL,
    'northwind' => <<<'SQL'
CREATE TABLE categories (category_id SERIAL PRIMARY KEY, category_name TEXT, description TEXT, picture BYTEA);
CREATE TABLE customers (customer_id TEXT PRIMARY KEY, company_name TEXT, contact_name TEXT, contact_title TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, phone TEXT, fax TEXT);
CREATE TABLE employees (employee_id SERIAL PRIMARY KEY, last_name TEXT, first_name TEXT, title TEXT, title_of_courtesy TEXT, birth_date TEXT, hire_date TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, home_phone TEXT, extension TEXT, photo BYTEA, notes TEXT, reports_to INTEGER, photo_path TEXT);
CREATE TABLE suppliers (supplier_id SERIAL PRIMARY KEY, company_name TEXT, contact_name TEXT, contact_title TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, phone TEXT, fax TEXT, homepage TEXT);
CREATE TABLE shippers ("ShipperID" SERIAL PRIMARY KEY, "CompanyName" TEXT, "Phone" TEXT);
CREATE TABLE regions ("RegionID" SERIAL PRIMARY KEY, "RegionDescription" TEXT);
CREATE TABLE territories ("TerritoryID" TEXT PRIMARY KEY, "TerritoryDescription" TEXT, "RegionID" INTEGER);
CREATE TABLE products (product_id SERIAL PRIMARY KEY, product_name TEXT, supplier_id INTEGER, category_id INTEGER, quantity_per_unit TEXT, unit_price REAL, units_in_stock INTEGER, units_on_order INTEGER, reorder_level INTEGER, discontinued INTEGER);
CREATE TABLE orders ("OrderID" SERIAL PRIMARY KEY, "CustomerID" TEXT, "EmployeeID" INTEGER, "OrderDate" TEXT, "RequiredDate" TEXT, "ShippedDate" TEXT, "ShipVia" INTEGER, "Freight" REAL, "ShipName" TEXT, "ShipAddress" TEXT, "ShipCity" TEXT, "ShipRegion" TEXT, "ShipPostalCode" TEXT, "ShipCountry" TEXT);
SQL,
    'pagila' => <<<'SQL'
CREATE TABLE country (country_id SERIAL PRIMARY KEY, country TEXT);
CREATE TABLE city (city_id SERIAL PRIMARY KEY, city TEXT, country_id INTEGER);
CREATE TABLE category (category_id SERIAL PRIMARY KEY, name TEXT);
CREATE TABLE language (language_id SERIAL PRIMARY KEY, name TEXT);
CREATE TABLE actor (actor_id SERIAL PRIMARY KEY, first_name TEXT, last_name TEXT);
CREATE TABLE store (store_id SERIAL PRIMARY KEY, manager_staff_id INTEGER);
CREATE TABLE staff (staff_id SERIAL PRIMARY KEY, first_name TEXT, last_name TEXT, email TEXT, store_id INTEGER, active BOOLEAN, username TEXT, password TEXT);
CREATE TABLE film (film_id SERIAL PRIMARY KEY, title TEXT, description TEXT, release_year INTEGER, language_id INTEGER, rental_duration INTEGER, rental_rate REAL, length INTEGER, replacement_cost REAL, rating TEXT, special_features TEXT);
CREATE TABLE customer (customer_id SERIAL PRIMARY KEY, store_id INTEGER, first_name TEXT, last_name TEXT, email TEXT, active BOOLEAN);
SQL,
];

/**
 * @return array{directory: string, existed: bool, files: array<string, string|null>}
 */
function installBehavioralImportFixture(string $product): array
{
    $fixture = BEHAVIORAL_IMPORT_FIXTURES[$product];
    /** @var array{product: string, commit_sha: string} $manifest */
    $manifest = require database_path("sources/{$product}.php");

    $directory = storage_path("app/private/sources/{$manifest['product']}/{$manifest['commit_sha']}");
    $directoryExisted = File::isDirectory($directory);
    $source = base_path($fixture['fixture']);
    $target = $directory.'/'.$fixture['filename'];
    $targets = [$target];

    if ($product === 'pagila') {
        $targets[] = $directory.'/pagila-schema.sql';
    }

    /** @var array<string, string|null> $backups */
    $backups = [];
    foreach ($targets as $path) {
        $backups[$path] = File::exists($path) ? File::get($path) : null;
    }

    File::ensureDirectoryExists(dirname($target));
    if ($product === 'pagila') {
        File::put($directory.'/pagila-schema.sql', '');
    }

    $contents = BEHAVIORAL_SOURCE_DDL[$product]."\n".File::get($source);
    $contents = str_replace($product.'_source.', 'public.', $contents);
    File::put($target, $contents);

    return ['directory' => $directory, 'existed' => $directoryExisted, 'files' => $backups];
}

/** @param array{directory: string, existed: bool, files: array<string, string|null>} $fixture */
function restoreBehavioralImportFixture(array $fixture): void
{
    foreach ($fixture['files'] as $path => $contents) {
        if ($contents === null) {
            File::delete($path);
        } else {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $contents);
        }
    }

    if (! $fixture['existed'] && File::isDirectory($fixture['directory'])) {
        File::deleteDirectory($fixture['directory']);
    }
}

/** @return list<string> */
function behavioralColumnIds(string $table, string $column): array
{
    return array_values(DB::table($table)->pluck($column)->map(function (mixed $value): string {
        if (! is_scalar($value)) {
            throw new UnexpectedValueException('Expected a scalar database identifier.');
        }

        return (string) $value;
    })->all());
}

function assertUuidV7Ids(string $table): void
{
    $ids = DB::table($table)->pluck('id');

    expect($ids)->not->toBeEmpty();

    foreach ($ids as $id) {
        expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
    }
}

function assertPublishedProduct(string $product, string $domainTable): void
{
    assertUuidV7Ids("{$product}.{$domainTable}");

    expect(Schema::hasTable("{$product}.search_projections"))->toBeTrue();
    $projections = DB::table("{$product}.search_projections")->get([
        'entity_type', 'weight_d_text', 'weight_c_text', 'weight_b_text', 'weight_a_text', 'embedding_state',
    ]);
    expect($projections)->not->toBeEmpty();
    foreach ($projections as $projection) {
        expect($projection->embedding_state)->toBe('lexical_only');
        expect($projection->weight_d_text)->not->toBeNull();
        expect($projection->entity_type)->not->toBeEmpty();
    }

    $view = DB::select('SELECT product, stats FROM product_portfolio_snapshots WHERE product = ?', [$product]);
    expect($view)->toHaveCount(1)
        ->and($view[0]->product)->toBe($product);
}

test('real fixture imports populate domains and preserve published read models', function (string $product, string $domainTable) {
    $fixture = installBehavioralImportFixture($product);

    try {
        $result = app(ProductImportPipeline::class)->run($product);

        expect($result['success'])->toBeTrue();
        $runId = $result['run_id'] ?? null;
        expect($runId)->toBeString()->not->toBeEmpty();
        $run = ResetRun::find($runId);
        expect($run)->not->toBeNull();
        assert($run instanceof ResetRun);
        expect($run->status)->toBe('succeeded');
        assertPublishedProduct($product, $domainTable);

        if ($product === 'chinook') {
            $albums = DB::table('chinook.albums')
                ->join('chinook.artists', 'chinook.albums.artist_id', '=', 'chinook.artists.id')
                ->pluck('chinook.artists.name', 'chinook.albums.title')
                ->all();
            expect($albums)->toMatchArray([
                'For Those About To Rock We Salute You' => 'AC/DC',
                'Balls to the Wall' => 'Accept',
            ]);
        }

        if ($product === 'northwind') {
            $products = DB::table('northwind.products')
                ->join('northwind.suppliers', 'northwind.products.supplier_id', '=', 'northwind.suppliers.id')
                ->join('northwind.categories', 'northwind.products.category_id', '=', 'northwind.categories.id')
                ->pluck('northwind.suppliers.company_name', 'northwind.products.product_name')
                ->all();
            expect($products)->toMatchArray([
                'Chai' => 'Exotic Liquids',
                'Chang' => 'Exotic Liquids',
            ]);
        }

        if ($product === 'pagila') {
            $films = DB::table('pagila.films')
                ->join('pagila.languages', 'pagila.films.language_id', '=', 'pagila.languages.id')
                ->pluck('pagila.languages.name', 'pagila.films.title')
                ->all();
            expect($films)->toMatchArray([
                'Academy Dinosaur' => 'English',
                'Ace Goldfinger' => 'English',
            ]);
            $storeIds = behavioralColumnIds('pagila.stores', 'id');
            $customerStoreIds = behavioralColumnIds('pagila.customers', 'store_id');
            expect(DB::table('pagila.customers')->count())->toBeGreaterThan(0)
                ->and(DB::table('pagila.customers')->whereNotNull('store_id')->count())
                ->toBe(DB::table('pagila.customers')->count())
                ->and(array_diff($customerStoreIds, $storeIds))->toBeEmpty();
        }
    } finally {
        restoreBehavioralImportFixture($fixture);
    }
})->with([
    'chinook' => ['chinook', 'artists'],
    'northwind' => ['northwind', 'products'],
    'pagila' => ['pagila', 'films'],
])->group('serial');
