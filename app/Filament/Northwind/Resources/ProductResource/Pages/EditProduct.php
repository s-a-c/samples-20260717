<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ProductResource\Pages;

use App\Filament\Northwind\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
