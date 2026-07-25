<?php

namespace App\Filament\Chinook\Resources\AlbumResource\Pages;

use App\Filament\Chinook\Resources\AlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlbum extends EditRecord
{
    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
