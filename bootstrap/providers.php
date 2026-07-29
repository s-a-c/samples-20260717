<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ChinookPanelProvider;
use App\Providers\Filament\NorthwindPanelProvider;
use App\Providers\Filament\PagilaPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ChinookPanelProvider::class,
    NorthwindPanelProvider::class,
    PagilaPanelProvider::class,
    FortifyServiceProvider::class,
];
