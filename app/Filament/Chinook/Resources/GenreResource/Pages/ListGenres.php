<?php

namespace App\Filament\Chinook\Resources\GenreResource\Pages;

use App\Filament\Chinook\Resources\GenreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGenres extends ListRecords
{
    protected static string $resource = GenreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
