<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\SupplierResource\Pages;

use App\Filament\Northwind\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
