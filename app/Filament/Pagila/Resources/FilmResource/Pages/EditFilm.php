<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\FilmResource\Pages;

use App\Filament\Pagila\Resources\FilmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFilm extends EditRecord
{
    protected static string $resource = FilmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
