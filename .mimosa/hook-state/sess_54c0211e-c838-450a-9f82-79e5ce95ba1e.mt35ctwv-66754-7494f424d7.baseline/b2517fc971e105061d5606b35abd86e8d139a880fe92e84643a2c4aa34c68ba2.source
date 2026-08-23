<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $product,
    ) {}

    public function handle(ProductImportPipeline $pipeline): void
    {
        $pipeline->run($this->product, false);
    }
}
