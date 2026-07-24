<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('chinook.invoices')]
class Invoice extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id');
    }
}
