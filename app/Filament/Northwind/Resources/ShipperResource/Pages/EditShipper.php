<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ShipperResource\Pages;

use App\Filament\Northwind\Resources\ShipperResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditShipper extends EditRecord
{
    protected static string $resource = ShipperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
