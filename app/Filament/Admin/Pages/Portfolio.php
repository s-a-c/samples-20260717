<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ProductPortfolioCard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Widgets\WidgetConfiguration;
use Override;

final class Portfolio extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected string $view = 'filament.admin.pages.portfolio';

    protected static ?string $slug = 'portfolio';

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            new WidgetConfiguration(ProductPortfolioCard::class, ['productKey' => 'chinook']),
            new WidgetConfiguration(ProductPortfolioCard::class, ['productKey' => 'northwind']),
            new WidgetConfiguration(ProductPortfolioCard::class, ['productKey' => 'pagila']),
        ];
    }
}
