<?php

declare(strict_types=1);

namespace App\Models\Chinook;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

#[Table('chinook.playlists')]
final class Playlist extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    #[Override]
    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Chinook;
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'chinook.playlist_track', 'playlist_id', 'track_id')
            ->using(PlaylistTrack::class);
    }
}
