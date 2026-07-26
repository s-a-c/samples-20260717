<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\CategoryResource\Pages;

use App\Filament\Northwind\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
