<?php

declare(strict_types=1);

namespace App\Services\Search;

final class ReciprocalRankFusion
{
    /**
     * Merge lexical and semantic rank lists using RRF formula: RRF_score = sum(1 / (k + rank)).
     *
     * @param  array<int, array{id: string, product: string, entity_type: string, title: string}>  $lexical
     * @param  array<int, array{id: string, product: string, entity_type: string, title: string}>  $semantic
     * @return array<int, array{id: string, product: string, entity_type: string, title: string, score: float}>
     */
    public function fuse(array $lexical, array $semantic, int $k = 60): array
    {
        $scores = [];
        $items = [];

        foreach ($lexical as $rank => $item) {
            $key = "{$item['product']}:{$item['id']}";
            $scores[$key] = ($scores[$key] ?? 0.0) + (1.0 / ($k + ($rank + 1)));
            $items[$key] = $item;
        }

        foreach ($semantic as $rank => $item) {
            $key = "{$item['product']}:{$item['id']}";
            $scores[$key] = ($scores[$key] ?? 0.0) + (1.0 / ($k + ($rank + 1)));
            $items[$key] = $item;
        }

        arsort($scores);

        $fused = [];
        foreach ($scores as $key => $score) {
            $item = $items[$key];
            $item['score'] = $score;
            $fused[] = $item;
        }

        return $fused;
    }
}
