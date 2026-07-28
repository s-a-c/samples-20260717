<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\AlbumResource\Pages;

use App\Filament\Chinook\Resources\AlbumResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAlbum extends CreateRecord
{
    protected static string $resource = AlbumResource::class;
}
