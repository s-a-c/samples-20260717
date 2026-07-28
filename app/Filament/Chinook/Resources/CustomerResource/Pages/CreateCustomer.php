<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\CustomerResource\Pages;

use App\Filament\Chinook\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
