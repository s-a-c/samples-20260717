<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * The kind of a {@see \App\Models\TeamArtefact}.
 *
 * Single-table discrimination: every Team Artefact row stores its kind here and
 * its type-specific payload in the row's JSONB configuration column.
 */
enum TeamArtefactType: string implements HasColor, HasIcon, HasLabel
{
    case SavedSearch = 'saved_search';
    case TeamDashboard = 'team_dashboard';

    public function getLabel(): string
    {
        return match ($this) {
            self::SavedSearch => 'Saved Search',
            self::TeamDashboard => 'Team Dashboard',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SavedSearch => 'info',
            self::TeamDashboard => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SavedSearch => Heroicon::OutlinedMagnifyingGlass,
            self::TeamDashboard => Heroicon::OutlinedSquares2x2,
        };
    }
}
