<?php

namespace App\Domain\Chinook\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('chinook.playlists')]
class Playlist extends Model
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'chinook';
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'chinook.playlist_track', 'playlist_id', 'track_id')
            ->using(PlaylistTrack::class);
    }
}
