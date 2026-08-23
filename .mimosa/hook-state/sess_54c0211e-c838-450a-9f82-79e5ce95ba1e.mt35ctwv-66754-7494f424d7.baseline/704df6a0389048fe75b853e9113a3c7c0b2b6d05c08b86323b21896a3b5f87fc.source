<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ProductResource\Pages;

use App\Filament\Northwind\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
