<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\InventoryResource\Pages;

use App\Filament\Pagila\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
