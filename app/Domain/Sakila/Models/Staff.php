<?php

namespace App\Domain\Sakila\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('sakila.staff')]
class Staff extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function getProductDomainName(): string
    {
        return 'sakila';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function managedStore(): HasOne
    {
        return $this->hasOne(Store::class, 'manager_staff_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'staff_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'staff_id');
    }
}
