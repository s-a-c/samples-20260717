<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ShipperResource\Pages;

use App\Filament\Northwind\Resources\ShipperResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListShippers extends ListRecords
{
    protected static string $resource = ShipperResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
