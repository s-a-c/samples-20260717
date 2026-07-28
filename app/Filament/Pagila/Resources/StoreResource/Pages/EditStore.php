<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\StoreResource\Pages;

use App\Filament\Pagila\Resources\StoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
