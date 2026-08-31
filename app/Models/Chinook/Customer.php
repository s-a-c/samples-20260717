<?php

declare(strict_types=1);

namespace App\Models\Chinook;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Table('chinook.customers')]
final class Customer extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Chinook;
    }

    public function supportRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'support_rep_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }
}
