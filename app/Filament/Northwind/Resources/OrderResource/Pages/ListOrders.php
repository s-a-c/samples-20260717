<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\OrderResource\Pages;

use App\Filament\Northwind\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
