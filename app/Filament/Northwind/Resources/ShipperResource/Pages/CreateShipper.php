<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\ShipperResource\Pages;

use App\Filament\Northwind\Resources\ShipperResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateShipper extends CreateRecord
{
    protected static string $resource = ShipperResource::class;
}
