<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources\OrderResource\Pages;

use App\Filament\Northwind\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
