<?php

namespace App\Filament\Pagila\Resources\ActorResource\Pages;

use App\Filament\Pagila\Resources\ActorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActor extends EditRecord
{
    protected static string $resource = ActorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
