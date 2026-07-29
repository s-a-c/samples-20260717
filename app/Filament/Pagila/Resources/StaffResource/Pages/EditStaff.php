<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\StaffResource\Pages;

use App\Filament\Pagila\Resources\StaffResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
