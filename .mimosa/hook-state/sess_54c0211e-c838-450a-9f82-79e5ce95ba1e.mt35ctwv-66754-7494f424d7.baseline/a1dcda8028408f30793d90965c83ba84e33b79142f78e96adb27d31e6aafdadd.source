<?php

declare(strict_types=1);

namespace App\Models\Pagila;

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pagila.film_actors')]
final class FilmActor extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomain(): SamplesProduct
    {
        return SamplesProduct::Pagila;
    }

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class, 'film_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }
}
