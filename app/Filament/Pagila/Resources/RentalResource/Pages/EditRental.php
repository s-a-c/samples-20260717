<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\RentalResource\Pages;

use App\Filament\Pagila\Resources\RentalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditRental extends EditRecord
{
    protected static string $resource = RentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
