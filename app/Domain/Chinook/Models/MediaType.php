<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('chinook.media_types')]
class MediaType extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class, 'media_type_id');
    }
}
