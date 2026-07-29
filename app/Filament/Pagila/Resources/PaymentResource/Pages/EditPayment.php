<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\PaymentResource\Pages;

use App\Filament\Pagila\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
