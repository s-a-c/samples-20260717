<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('chinook.invoice_lines')]
class InvoiceLine extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
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
