<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\TrackResource\Pages;

use App\Filament\Chinook\Resources\TrackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTrack extends EditRecord
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
