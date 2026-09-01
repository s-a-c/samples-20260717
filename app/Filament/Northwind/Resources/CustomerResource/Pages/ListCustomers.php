<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\CustomerResource\Pages;

use App\Filament\Northwind\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
