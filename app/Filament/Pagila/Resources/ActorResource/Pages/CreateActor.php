<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\ActorResource\Pages;

use App\Filament\Pagila\Resources\ActorResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateActor extends CreateRecord
{
    protected static string $resource = ActorResource::class;
}
