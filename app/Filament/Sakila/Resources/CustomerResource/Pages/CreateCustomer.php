<?php

namespace App\Filament\Sakila\Resources\CustomerResource\Pages;

use App\Filament\Sakila\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
