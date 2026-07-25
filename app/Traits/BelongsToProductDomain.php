<?php

namespace App\Traits;

use App\Contracts\HasProductDomain;
use App\Services\ProductReset\ResetWindow;

/**
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 * @method static void deleting(\Closure $callback)
 */
trait BelongsToProductDomain
{
    public static function bootBelongsToProductDomain(): void
    {
        static::creating(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::updating(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::deleting(function (HasProductDomain $model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });
    }

    abstract public function getProductDomainName(): string;
}
