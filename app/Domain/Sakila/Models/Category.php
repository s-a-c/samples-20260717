<?php

namespace App\Domain\Sakila\Models;

use App\Contracts\HasProductDomain;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('sakila.categories')]
final class Category extends Model implements HasProductDomain
{
    use BelongsToProductDomain, HasUuids;

    protected $guarded = [];

    public function getProductDomainName(): string
    {
        return 'sakila';
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'sakila.film_categories', 'category_id', 'film_id');
    }

    public function filmCategories(): HasMany
    {
        return $this->hasMany(FilmCategory::class, 'category_id');
    }
}
