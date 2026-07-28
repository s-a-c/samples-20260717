<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\InventoryResource\Pages;

use App\Filament\Pagila\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
