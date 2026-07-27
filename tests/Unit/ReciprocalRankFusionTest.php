<?php

declare(strict_types=1);

use App\Services\Search\ReciprocalRankFusion;

covers(ReciprocalRankFusion::class);

/**
 * @return array{id: string, product: string, entity_type: string, title: string}
 */
function rrfItem(string $id, string $product = 'chinook', string $type = 'track', string $title = 'T'): array
{
    return ['id' => $id, 'product' => $product, 'entity_type' => $type, 'title' => $title];
}

test('rrf scores a single lexical item as 1 over (k+1) with default k=60', function () {
    $result = (new ReciprocalRankFusion)->fuse([rrfItem('a')], []);

    expect($result)->toHaveCount(1)
        ->and($result[0]['score'])->toBe(1.0 / (60 + 1));
});

test('rrf sums contributions when an item appears in both lists', function () {
    $item = rrfItem('a');

    $result = (new ReciprocalRankFusion)->fuse([$item], [$item]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['score'])->toBe(2.0 / (60 + 1));
});

test('rrf honours a custom k value in the score formula', function () {
    $result = (new ReciprocalRankFusion)->fuse([rrfItem('a')], [], k: 1);

    expect($result[0]['score'])->toBe(1.0 / (1 + 1));
});

test('rrf returns an empty array when both lists are empty', function () {
    expect((new ReciprocalRankFusion)->fuse([], []))->toBe([]);
});

test('rrf scores a single list when the other is empty', function () {
    $result = (new ReciprocalRankFusion)->fuse([rrfItem('a'), rrfItem('b')], []);

    expect($result)->toHaveCount(2)
        ->and($result[0]['score'])->toBe(1.0 / 61)   // rank 0
        ->and($result[1]['score'])->toBe(1.0 / 62);  // rank 1
});

test('rrf ranks items appearing in both lists above items in only one', function () {
    $shared = rrfItem('shared');
    $onlyLexical = rrfItem('only-lex');

    $result = (new ReciprocalRankFusion)->fuse([$shared, $onlyLexical], [$shared]);

    // shared: 1/61 + 1/61 ≈ 0.0328  >  only-lex: 1/62 ≈ 0.0161
    expect($result[0]['id'])->toBe('shared')
        ->and($result[1]['id'])->toBe('only-lex');
});

test('rrf deduplicates items by product:id across the two lists', function () {
    $item = rrfItem('a');

    // The same key repeated in one list still contributes once per occurrence;
    // across lists the key collapses to a single fused item.
    $result = (new ReciprocalRankFusion)->fuse([$item, $item], [$item]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe('a');
});

test('rrf attaches the score to each fused item and preserves identity fields', function () {
    $result = (new ReciprocalRankFusion)->fuse([rrfItem('a', 'pagila', 'film', 'Title')], []);

    expect($result[0])->toHaveKey('score')
        ->and($result[0]['product'])->toBe('pagila')
        ->and($result[0]['entity_type'])->toBe('film')
        ->and($result[0]['title'])->toBe('Title');
});
