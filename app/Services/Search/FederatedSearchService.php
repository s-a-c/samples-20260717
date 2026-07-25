<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;

final class FederatedSearchService
{
    public function __construct(
        private ReciprocalRankFusion $rrf = new ReciprocalRankFusion,
        private SearchDeepLinkRegistry $registry = new SearchDeepLinkRegistry
    ) {}

    /**
     * Perform federated search across product schemas with RRF ranking and deep links.
     *
     * @param  array<float>|null  $embedding
     * @return array<int, array{id: string, product: string, entity_type: string, title: string, score: float, url: string}>
     */
    public function search(string $query, ?array $embedding = null): array
    {
        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return [];
        }

        $products = ['chinook', 'northwind', 'sakila'];

        // Lexical Full-Text Search
        $lexicalUnions = [];
        $lexicalParams = [];

        foreach ($products as $p) {
            $lexicalUnions[] = "
                SELECT id, '{$p}' AS product, entity_type, weight_d_text AS title,
                       ts_rank_cd(document_tsv, websearch_to_tsquery('en_unaccent', ?)) AS rank
                FROM {$p}.search_projections
                WHERE document_tsv @@ websearch_to_tsquery('en_unaccent', ?)
            ";
            $lexicalParams[] = $trimmedQuery;
            $lexicalParams[] = $trimmedQuery;
        }

        $lexicalSql = implode(' UNION ALL ', $lexicalUnions).' ORDER BY rank DESC LIMIT 50';
        $lexicalRows = DB::select($lexicalSql, $lexicalParams);
        $lexical = array_map(fn ($r) => (array) $r, $lexicalRows);

        // Semantic Vector Search (if embedding is provided)
        $semantic = [];
        if ($embedding !== null) {
            $vecStr = '['.implode(',', $embedding).']';
            $semanticUnions = [];
            $semanticParams = [];

            foreach ($products as $p) {
                $semanticUnions[] = "
                    SELECT id, '{$p}' AS product, entity_type, weight_d_text AS title,
                           (embedding <=> ?::vector) AS distance
                    FROM {$p}.search_projections
                    WHERE embedding IS NOT NULL
                ";
                $semanticParams[] = $vecStr;
            }

            $semanticSql = implode(' UNION ALL ', $semanticUnions).' ORDER BY distance ASC LIMIT 50';
            $semanticRows = DB::select($semanticSql, $semanticParams);
            $semantic = array_map(fn ($r) => (array) $r, $semanticRows);
        }

        $fused = $this->rrf->fuse($lexical, $semantic);

        foreach ($fused as &$item) {
            $item['url'] = $this->registry->getUrl($item['product'], $item['entity_type'], $item['id']);
        }

        return $fused;
    }
}
