<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\SamplesProduct;
use App\Jobs\EmbeddingJob;
use Illuminate\Database\Eloquent\Model;

final class Tier1SourceObserver
{
    /**
     * Handle the Model "saved" event.
     */
    public function saved(Model $model): void
    {
        if (app()->bound('is_staging') && app('is_staging') === true) {
            return;
        }

        $product = method_exists($model, 'getProductDomain')
            ? $model->getProductDomain()
            : SamplesProduct::Chinook;

        if (class_exists(EmbeddingJob::class)) {
            EmbeddingJob::dispatch($product->value, $model->getKey() ?? '');
        }
    }
}
