<?php

namespace App\Domain\Pagila\Models;

use App\Contracts\HasProductDomain;
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

    public function getProductDomainName(): string
    {
        return 'pagila';
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
