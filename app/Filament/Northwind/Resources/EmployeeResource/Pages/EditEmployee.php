<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\EmployeeResource\Pages;

use App\Filament\Northwind\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
