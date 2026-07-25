<?php

namespace App\Filament\Sakila\Resources\CategoryResource\Pages;

use App\Filament\Sakila\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
