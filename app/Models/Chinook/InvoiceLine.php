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
use Override;

#[Table('chinook.invoice_lines')]
final class InvoiceLine extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Chinook;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'track_id');
    }
}
