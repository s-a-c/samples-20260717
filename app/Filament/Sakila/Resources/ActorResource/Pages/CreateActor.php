<?php

namespace App\Filament\Sakila\Resources\ActorResource\Pages;

use App\Filament\Sakila\Resources\ActorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActor extends CreateRecord
{
    protected static string $resource = ActorResource::class;
}
