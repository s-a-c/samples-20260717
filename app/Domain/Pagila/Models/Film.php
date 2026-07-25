<?php

namespace App\Domain\Pagila\Models;

use App\Contracts\HasProductDomain;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pagila.films')]
final class Film extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'release_year' => 'integer',
            'rental_duration' => 'integer',
            'rental_rate' => 'decimal:2',
            'length' => 'integer',
            'replacement_cost' => 'decimal:2',
        ];
    }

    public function getProductDomainName(): string
    {
        return 'pagila';
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function originalLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'original_language_id');
    }

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'pagila.film_actors', 'film_id', 'actor_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'pagila.film_categories', 'film_id', 'category_id');
    }

    public function filmActors(): HasMany
    {
        return $this->hasMany(FilmActor::class, 'film_id');
    }

    public function filmCategories(): HasMany
    {
        return $this->hasMany(FilmCategory::class, 'film_id');
    }

    public function filmTexts(): HasMany
    {
        return $this->hasMany(FilmText::class, 'film_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'film_id');
    }
}
