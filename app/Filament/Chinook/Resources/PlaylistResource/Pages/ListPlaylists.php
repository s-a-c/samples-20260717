<?php

namespace App\Filament\Chinook\Resources\PlaylistResource\Pages;

use App\Filament\Chinook\Resources\PlaylistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlaylists extends ListRecords
{
    protected static string $resource = PlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
