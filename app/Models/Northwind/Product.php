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
use Override;

#[Table('northwind.products')]
final class Product extends Model implements HasProductDomain
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
            'unit_price' => 'decimal:2',
            'discontinued' => 'boolean',
            'units_in_stock' => 'integer',
            'units_on_order' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Northwind;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'northwind.order_details', 'product_id', 'order_id')
            ->withPivot(['unit_price', 'quantity', 'discount'])
            ->withTimestamps();
    }
}
