<?php

namespace App\Observers;

use App\Jobs\EmbeddingJob;
use Illuminate\Database\Eloquent\Model;

class Tier1SourceObserver
{
    /**
     * Handle the Model "saved" event.
     */
    public function saved(Model $model): void
    {
        if (app()->bound('is_staging') && app('is_staging') === true) {
            return;
        }

        /** @var string $product */
        $product = method_exists($model, 'getProductDomainName')
            ? $model->getProductDomainName()
            : 'chinook';

        if (class_exists(EmbeddingJob::class)) {
            EmbeddingJob::dispatch($product, $model->getKey() ?? '');
        }
    }
}
