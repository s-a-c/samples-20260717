<?php

declare(strict_types=1);

use App\Jobs\EmbeddingJob;
use App\Models\Chinook\Artist;
use App\Models\Northwind\Product;
use App\Models\Pagila\Film;
use App\Models\Pagila\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

covers(Artist::class);

uses(RefreshDatabase::class);

test('chinook artist insertion automatically populates search projection table via trigger', function () {
    Queue::fake();

    $artist = Artist::create(['name' => 'Queen']);

    $projection = DB::selectOne('SELECT * FROM chinook.search_projections WHERE id = ?', [$artist->id]);

    expect($projection)->not->toBeNull();
    expect($projection->weight_d_text)->toBe('Queen');
    expect($projection->entity_type)->toBe('artist');
    expect($projection->embedding_state)->toBe('pending');
    expect($projection->document_tsv)->not->toBeNull();

    Queue::assertPushed(EmbeddingJob::class, function ($job) use ($artist) {
        return $job->product === 'chinook' && $job->entityId === $artist->id;
    });
});

test('northwind product insertion automatically populates search projection table via trigger', function () {
    Queue::fake();

    $product = Product::create(['product_name' => 'Chai']);

    $projection = DB::selectOne('SELECT * FROM northwind.search_projections WHERE id = ?', [$product->id]);

    expect($projection)->not->toBeNull();
    expect($projection->weight_d_text)->toBe('Chai');
    expect($projection->entity_type)->toBe('product');
    expect($projection->embedding_state)->toBe('pending');
    expect($projection->document_tsv)->not->toBeNull();

    Queue::assertPushed(EmbeddingJob::class, function ($job) use ($product) {
        return $job->product === 'northwind' && $job->entityId === $product->id;
    });
});

test('pagila film insertion automatically populates search projection table via trigger', function () {
    Queue::fake();

    $language = Language::create(['name' => 'English']);

    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'description' => 'A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies',
        'language_id' => $language->id,
    ]);

    $projection = DB::selectOne('SELECT * FROM pagila.search_projections WHERE id = ?', [$film->id]);

    expect($projection)->not->toBeNull();
    expect($projection->weight_d_text)->toBe('ACADEMY DINOSAUR');
    expect($projection->weight_c_text)->toBe('A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies');
    expect($projection->entity_type)->toBe('film');
    expect($projection->embedding_state)->toBe('pending');
    expect($projection->document_tsv)->not->toBeNull();

    Queue::assertPushed(EmbeddingJob::class, function ($job) use ($film) {
        return $job->product === 'pagila' && $job->entityId === $film->id;
    });
});

test('updating source model updates search projection', function () {
    $artist = Artist::create(['name' => 'Original Name']);

    $artist->update(['name' => 'Updated Name']);

    $projection = DB::selectOne('SELECT * FROM chinook.search_projections WHERE id = ?', [$artist->id]);

    expect($projection)->not->toBeNull();
    expect($projection->weight_d_text)->toBe('Updated Name');
});

test('deleting source model removes search projection', function () {
    $artist = Artist::create(['name' => 'To Be Deleted']);

    $artist->delete();

    $projection = DB::selectOne('SELECT * FROM chinook.search_projections WHERE id = ?', [$artist->id]);

    expect($projection)->toBeNull();
});
