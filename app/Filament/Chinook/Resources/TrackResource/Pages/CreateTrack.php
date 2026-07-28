<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\TrackResource\Pages;

use App\Filament\Chinook\Resources\TrackResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTrack extends CreateRecord
{
    protected static string $resource = TrackResource::class;
}
