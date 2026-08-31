<?php

declare(strict_types=1);

namespace App\Models\Northwind;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Table('northwind.shippers')]
final class Shipper extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Northwind;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'ship_via');
    }
}
