<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\LanguageResource\Pages;

use App\Filament\Pagila\Resources\LanguageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
