<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $product,
        public string $entityId,
    ) {}

    public function handle(): void
    {
        // Handled in Task 11
    }
}
