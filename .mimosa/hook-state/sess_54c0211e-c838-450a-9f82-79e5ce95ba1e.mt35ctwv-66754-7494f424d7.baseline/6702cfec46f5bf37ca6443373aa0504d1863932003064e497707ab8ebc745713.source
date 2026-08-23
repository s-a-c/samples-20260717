<?php

declare(strict_types=1);

namespace App\Models\Pagila;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pagila.actors')]
final class Actor extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Pagila;
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'pagila.film_actors', 'actor_id', 'film_id');
    }

    public function filmActors(): HasMany
    {
        return $this->hasMany(FilmActor::class, 'actor_id');
    }
}
