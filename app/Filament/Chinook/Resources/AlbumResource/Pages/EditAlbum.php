<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\AlbumResource\Pages;

use App\Filament\Chinook\Resources\AlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAlbum extends EditRecord
{
    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
