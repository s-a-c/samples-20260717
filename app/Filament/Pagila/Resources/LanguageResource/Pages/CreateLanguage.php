<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources\LanguageResource\Pages;

use App\Filament\Pagila\Resources\LanguageResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;
}
