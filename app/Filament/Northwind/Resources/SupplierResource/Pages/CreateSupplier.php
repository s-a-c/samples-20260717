<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\SupplierResource\Pages;

use App\Filament\Northwind\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;
}
