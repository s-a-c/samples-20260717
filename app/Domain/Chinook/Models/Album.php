<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('chinook.albums')]
class Album extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class, 'album_id');
    }
}
