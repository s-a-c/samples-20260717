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

#[Table('northwind.orders')]
final class Order extends Model implements HasProductDomain
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
            'order_date' => 'datetime',
            'required_date' => 'datetime',
            'shipped_date' => 'datetime',
            'freight' => 'decimal:2',
        ];
    }

    public function getProductDomainName(): string
    {
        return 'northwind';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class, 'ship_via');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'northwind.order_details', 'order_id', 'product_id')
            ->withPivot(['unit_price', 'quantity', 'discount'])
            ->withTimestamps();
    }
}
