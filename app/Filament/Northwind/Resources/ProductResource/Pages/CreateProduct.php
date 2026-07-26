<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ProductResource\Pages;

use App\Filament\Northwind\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
