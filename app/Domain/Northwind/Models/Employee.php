<?php

namespace App\Domain\Northwind\Models;

use App\Contracts\HasProductDomain;
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

    public function getProductDomainName(): string
    {
        return 'northwind';
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reports_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reports_to');
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
