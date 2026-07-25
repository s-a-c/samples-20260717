<?php

namespace App\Filament\Sakila\Resources\FilmResource\Pages;

use App\Filament\Sakila\Resources\FilmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFilm extends EditRecord
{
    protected static string $resource = FilmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
