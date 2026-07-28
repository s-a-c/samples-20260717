<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\CustomerResource\Pages;

use App\Filament\Pagila\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
