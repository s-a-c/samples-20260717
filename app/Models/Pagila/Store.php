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

#[Table('pagila.stores')]
final class Store extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Pagila;
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_staff_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'store_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'store_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'store_id');
    }
}
