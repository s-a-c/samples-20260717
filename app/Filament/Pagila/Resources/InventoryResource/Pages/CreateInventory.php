<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\InventoryResource\Pages;

use App\Filament\Pagila\Resources\InventoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;
}
