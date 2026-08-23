<?php

declare(strict_types=1);

use App\Models\Chinook\Artist;
use App\Models\Northwind\Product;
use App\Models\Pagila\Film;
use App\Models\Pagila\Language;
use App\Services\Search\FederatedSearchService;
use App\Services\Search\ReciprocalRankFusion;
use App\Services\Search\SearchDeepLinkRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

covers(FederatedSearchService::class, SearchDeepLinkRegistry::class);

uses(RefreshDatabase::class);

test('reciprocal rank fusion correctly combines lexical and semantic rank lists with k=60', function () {
    $rrf = new ReciprocalRankFusion;

    $lexical = [
        ['id' => '1', 'product' => 'chinook', 'entity_type' => 'artist', 'title' => 'Metallica'],
        ['id' => '2', 'product' => 'northwind', 'entity_type' => 'product', 'title' => 'Metal Pipe'],
    ];

    $semantic = [
        ['id' => '2', 'product' => 'northwind', 'entity_type' => 'product', 'title' => 'Metal Pipe'],
        ['id' => '3', 'product' => 'pagila', 'entity_type' => 'film', 'title' => 'Metal Academy'],
    ];

    $results = $rrf->fuse($lexical, $semantic, 60);

    // Item '2' (northwind) rank 1 in lexical (index 1 => rank 2) and rank 0 in semantic (index 0 => rank 1)
    // Score for item 2 = (1 / (60 + 2)) + (1 / (60 + 1)) = (1 / 62) + (1 / 61) = 0.01612903 + 0.01639344 = 0.03252247
    // Score for item 1 = (1 / (60 + 1)) = 1 / 61 = 0.01639344
    // Score for item 3 = (1 / (60 + 2)) = 1 / 62 = 0.01612903

    expect($results)->toHaveCount(3);
    expect($results[0]['id'])->toBe('2');
    expect($results[0]['product'])->toBe('northwind');
    expect($results[0]['score'])->toEqualWithDelta((1 / 62) + (1 / 61), 0.00001);

    expect($results[1]['id'])->toBe('1');
    expect($results[1]['score'])->toEqualWithDelta(1 / 61, 0.00001);

    expect($results[2]['id'])->toBe('3');
    expect($results[2]['score'])->toEqualWithDelta(1 / 62, 0.00001);
});

test('search deep link registry resolves correct url paths', function () {
    $registry = new SearchDeepLinkRegistry;

    expect($registry->getUrl('chinook', 'artist', 'uuid-123'))->toBe('/chinook/artists/uuid-123');
    expect($registry->getUrl('northwind', 'product', 'uuid-456'))->toBe('/northwind/products/uuid-456');
    expect($registry->getUrl('pagila', 'film', 'uuid-789'))->toBe('/pagila/films/uuid-789');
});

test('federated search executes 3-way union all across projection tables and returns rrf ranked results with deep links', function () {
    $artist = Artist::create(['name' => 'Metallica']);
    $product = Product::create(['product_name' => 'Metallica CD']);

    $language = Language::create(['name' => 'English']);
    $film = Film::create([
        'title' => 'METALLICA DOC',
        'description' => 'A documentary about Metallica',
        'language_id' => $language->id,
    ]);

    $service = new FederatedSearchService;
    $results = $service->search('Metallica');

    expect($results)->not->toBeEmpty();

    $productsFound = array_column($results, 'product');
    expect($productsFound)->toContain('chinook');
    expect($productsFound)->toContain('northwind');
    expect($productsFound)->toContain('pagila');

    foreach ($results as $item) {
        expect($item)->toHaveKeys(['id', 'product', 'entity_type', 'title', 'score', 'url']);
        expect($item['url'])->toBe("/{$item['product']}/".Str::plural($item['entity_type'])."/{$item['id']}");
    }
});

test('federated search returns empty array for blank query', function () {
    $service = new FederatedSearchService;

    expect($service->search(''))->toBe([]);
    expect($service->search('   '))->toBe([]);
});

test('federated search supports semantic vector search when embedding is provided', function () {
    $artist = Artist::create(['name' => 'Pink Floyd']);

    $language = Language::create(['name' => 'English2']);
    $film = Film::create([
        'title' => 'Pink Floyd Live',
        'description' => 'A concert film',
        'language_id' => $language->id,
    ]);

    $service = new FederatedSearchService;
    $embedding = array_fill(0, 1024, 0.0);
    $results = $service->search('Pink Floyd', $embedding);

    expect($results)->not->toBeEmpty();

    foreach ($results as $item) {
        expect($item)->toHaveKey('url');
        expect($item['url'])->toStartWith('/');
    }
});
