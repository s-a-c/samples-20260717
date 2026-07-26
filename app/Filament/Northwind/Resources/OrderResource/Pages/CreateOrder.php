<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\OrderResource\Pages;

use App\Filament\Northwind\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
