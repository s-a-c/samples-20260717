<?php

namespace App\Filament\Chinook\Resources\PlaylistResource\Pages;

use App\Filament\Chinook\Resources\PlaylistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlaylist extends EditRecord
{
    protected static string $resource = PlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
