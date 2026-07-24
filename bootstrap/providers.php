<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ChinookPanelProvider;
use App\Providers\Filament\NorthwindPanelProvider;
use App\Providers\Filament\SakilaPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ChinookPanelProvider::class,
    NorthwindPanelProvider::class,
    SakilaPanelProvider::class,
    FortifyServiceProvider::class,
];
