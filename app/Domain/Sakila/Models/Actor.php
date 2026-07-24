<?php

namespace App\Domain\Sakila\Models;

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('sakila.actors')]
class Actor extends Model
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
