<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait GeneratesUniqueTeamSlugs
{
    /**
     * Generate a unique slug for the team.
     */
    public static function generateUniqueTeamSlug(string $name, int|string|null $excludeId = null): string
    {
        $defaultSlug = Str::slug($name);

        $query = static::withTrashed()
            ->where(function (Builder $query) use ($defaultSlug): void {
                $query->where('slug', $defaultSlug)
                    ->orWhere('slug', 'like', $defaultSlug.'-%');
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSlugs = $query->pluck('slug');

        $maxSuffix = $existingSlugs
            ->map(function (mixed $slug) use ($defaultSlug): ?int {
                // PostgreSQL casts the slug column to string; non-string values
                // cannot reach this callback in production.
                // @codeCoverageIgnoreStart
                if (! is_string($slug)) {
                    return null;
                }
                // @codeCoverageIgnoreEnd
                if ($slug === $defaultSlug) {
                    return 0;
                }
                if (preg_match('/^'.preg_quote($defaultSlug, '/').'-(\d+)$/', $slug, $matches) === 1) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn (?int $suffix) => $suffix !== null)
            ->max() ?? 0;

        return $existingSlugs->isEmpty()
            ? $defaultSlug
            : $defaultSlug.'-'.($maxSuffix + 1);
    }
}
