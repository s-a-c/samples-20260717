<?php

namespace App\Filament\Pagila\Resources\ActorResource\Pages;

use App\Filament\Pagila\Resources\ActorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActors extends ListRecords
{
    protected static string $resource = ActorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
