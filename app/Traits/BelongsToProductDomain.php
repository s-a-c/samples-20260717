<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Services\ProductReset\ResetWindow;

/**
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 * @method static void deleting(\Closure $callback)
 */
trait BelongsToProductDomain
{
    abstract public function getProductDomain(): SamplesProduct;

    public static function bootBelongsToProductDomain(): void
    {
        static::creating(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomain());
        });

        static::updating(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomain());
        });

        static::deleting(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomain());
        });
    }
}
