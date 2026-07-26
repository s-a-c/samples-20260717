<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\EmployeeResource\Pages;

use App\Filament\Northwind\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
