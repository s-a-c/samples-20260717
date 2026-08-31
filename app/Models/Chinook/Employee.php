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

#[Table('chinook.employees')]
final class Employee extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Chinook;
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'support_rep_id');
    }
}
