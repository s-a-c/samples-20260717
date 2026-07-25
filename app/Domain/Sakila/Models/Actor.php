<?php

namespace App\Domain\Sakila\Models;

use App\Contracts\HasProductDomain;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('sakila.actors')]
final class Actor extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'sakila';
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'sakila.film_actors', 'actor_id', 'film_id');
    }

    public function filmActors(): HasMany
    {
        return $this->hasMany(FilmActor::class, 'actor_id');
    }
}
