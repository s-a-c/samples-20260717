<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * The closed registry of Sample Products presented by this application.
 *
 * Each case is one independently recognisable reference dataset and its
 * Sample Panel. Adding a Sample Product is a single new {@see profile()}
 * match arm; membership checks use {@see tryFrom()} so they extend
 * automatically. The Admin Panel is intentionally NOT a member — it is the
 * cross-product Core Application, not a Sample Product.
 */
enum SamplesProduct: string implements HasColor, HasIcon, HasLabel
{
    case Chinook = 'chinook';
    case Northwind = 'northwind';
    case Pagila = 'pagila';

    /**
     * Resolve a Sample Product from a Filament panel identifier.
     *
     * Returns null when the panel id is not a Sample Product (e.g. the
     * Admin Panel), so callers can gate on Sample Product membership
     * without maintaining a parallel list of identifiers.
     */
    public static function fromPanelId(string $panelId): ?self
    {
        return self::tryFrom($panelId);
    }

    /**
     * The complete identity bundle for this Sample Product.
     *
     * This is the only place per-product data lives; every other method
     * delegates here. Adding a product means adding exactly one arm.
     */
    public function profile(): ProductProfile
    {
        return match ($this) {
            self::Chinook => new ProductProfile(
                label: 'Chinook',
                color: 'violet',
                filamentColor: Color::Violet,
                icon: Heroicon::OutlinedMusicalNote,
                curatorRole: 'chinook_curator',
                path: 'chinook',
                description: 'Digital media store sample dataset featuring artists, albums, tracks, and customers — showcasing a music sales platform.',
            ),
            self::Northwind => new ProductProfile(
                label: 'Northwind',
                color: 'sky',
                filamentColor: Color::Sky,
                icon: Heroicon::OutlinedTruck,
                curatorRole: 'northwind_curator',
                path: 'northwind',
                description: 'Classic order-management sample dataset with products, suppliers, customers, and orders — demonstrating a trading enterprise.',
            ),
            self::Pagila => new ProductProfile(
                label: 'Pagila',
                color: 'rose',
                filamentColor: Color::Rose,
                icon: Heroicon::OutlinedFilm,
                curatorRole: 'pagila_curator',
                path: 'pagila',
                description: 'DVD rental store sample dataset featuring films, actors, customers, and rentals — illustrating a rental business domain.',
            ),
        };
    }

    public function getLabel(): string
    {
        return $this->profile()->label;
    }

    public function getColor(): string
    {
        return $this->profile()->color;
    }

    public function getIcon(): Heroicon
    {
        return $this->profile()->icon;
    }

    /**
     * Full 50-950 Tailwind palette for Filament panel colour registration.
     *
     * @return array<int, string>
     */
    public function filamentColor(): array
    {
        return $this->profile()->filamentColor;
    }

    /**
     * The Spatie role granting Sample Curator authority for this product.
     */
    public function curatorRole(): string
    {
        return $this->profile()->curatorRole;
    }

    /**
     * The Filament panel identifier (also the URL path segment).
     */
    public function panelId(): string
    {
        return $this->profile()->path;
    }

    public function panelPath(): string
    {
        return $this->profile()->path;
    }

    public function url(): string
    {
        return '/'.$this->profile()->path;
    }

    public function description(): string
    {
        return $this->profile()->description;
    }

    public function icon(): Heroicon
    {
        return $this->profile()->icon;
    }
}
