<?php

declare(strict_types=1);

namespace App\Models\Pagila;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pagila.addresses')]
final class Address extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Pagila;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'address_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'address_id');
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'address_id');
    }
}
