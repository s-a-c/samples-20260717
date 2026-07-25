<?php

namespace App\Filament\Sakila\Resources\LanguageResource\Pages;

use App\Filament\Sakila\Resources\LanguageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
