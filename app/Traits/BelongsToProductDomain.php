<?php

namespace App\Traits;

use App\Services\ProductReset\ResetWindow;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 * @method static void deleting(\Closure $callback)
 */
trait BelongsToProductDomain
{
    public static function bootBelongsToProductDomain(): void
    {
        static::creating(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::updating(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });

        static::deleting(function ($model) {
            app(ResetWindow::class)->assertWritable($model->getProductDomainName());
        });
    }

    abstract public function getProductDomainName(): string;
}
