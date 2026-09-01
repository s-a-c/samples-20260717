<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ProductResource\Pages;

use App\Filament\Northwind\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
