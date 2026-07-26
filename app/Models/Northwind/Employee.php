<?php

declare(strict_types=1);

namespace App\Models\Northwind;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('northwind.employees')]
final class Employee extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'datetime',
            'hire_date' => 'datetime',
        ];
    }

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Northwind;
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'employee_id');
    }

    public function territories(): BelongsToMany
    {
        return $this->belongsToMany(Territory::class, 'northwind.employee_territories', 'employee_id', 'territory_id')
            ->withTimestamps();
    }
}
