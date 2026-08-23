<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

/**
 * The immutable identity bundle for one Sample Product.
 *
 * Co-located so that adding a Sample Product is a single new match arm on
 * {@see SamplesProduct::profile()} rather than edits across many methods.
 */
final readonly class ProductProfile
{
    /**
     * @param  array<int, string>  $filamentColor  Full 50-950 Tailwind palette for Filament panel registration.
     * @param  string  $color  Filament colour name used by {@see \Filament\Support\Contracts\HasColor}.
     * @param  string  $curatorRole  The Spatie role that grants Sample Curator authority for this product.
     * @param  string  $path  URL path segment and Filament panel identifier for this Sample Panel.
     */
    public function __construct(
        public string $label,
        public string $color,
        public array $filamentColor,
        public Heroicon $icon,
        public string $curatorRole,
        public string $path,
        public string $description,
    ) {}
}
