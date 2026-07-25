<?php

namespace App\Filament\Sakila\Resources\PaymentResource\Pages;

use App\Filament\Sakila\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
