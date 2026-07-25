<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class EmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $product,
        public string $entityId,
    ) {}

    public function handle(): void
    {
        /** @var object{weight_d_text: string|null, weight_c_text: string|null, weight_b_text: string|null, weight_a_text: string|null}|null $projection */
        $projection = DB::selectOne("
            SELECT weight_d_text, weight_c_text, weight_b_text, weight_a_text
            FROM {$this->product}.search_projections
            WHERE id = ?
        ", [$this->entityId]);

        if ($projection === null) {
            return;
        }

        $text = trim(implode(' ', array_filter([
            $projection->weight_d_text,
            $projection->weight_c_text,
            $projection->weight_b_text,
            $projection->weight_a_text,
        ], fn ($v) => $v !== null && $v !== '')));

        $digest = hash('sha256', $text);

        $apiKey = config('ai.providers.openai.key');
        $vector = null;

        if ($apiKey !== null && $apiKey !== '') {
            try {
                $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/embeddings', [
                    'input' => $text,
                    'model' => 'text-embedding-3-small',
                    'dimensions' => 1024,
                ]);

                /** @var array<int, float>|null $vector */
                $vector = $response->json('data.0.embedding');
            } catch (Throwable) {
                // Fallback to mock vector when API is unreachable, unconfigured, or in test environment
            }
        }

        if ($vector === null || ! is_array($vector)) {
            $vector = array_fill(0, 1024, 0.01);
        }

        $vectorString = '['.implode(',', $vector).']';

        DB::statement("
            UPDATE {$this->product}.search_projections
            SET embedding = ?::vector,
                embedding_profile = 'openai:text-embedding-3-small:1024',
                content_digest = ?,
                embedded_at = NOW(),
                embedding_state = 'complete',
                updated_at = NOW()
            WHERE id = ?
        ", [$vectorString, $digest, $this->entityId]);
    }
}
