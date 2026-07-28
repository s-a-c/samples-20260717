<?php

declare(strict_types=1);

namespace App\Filament\Chinook\Resources\InvoiceResource\Pages;

use App\Filament\Chinook\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
}
