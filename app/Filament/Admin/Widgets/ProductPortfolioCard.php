<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\SamplesProduct;
use App\Exceptions\ProductResetWindowOpen;
use App\Jobs\ProductImportJob;
use App\Models\ResetRun;
use App\Services\Portfolio\PortfolioSnapshotStats;
use App\Services\ProductReset\ResetWindow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

final class ProductPortfolioCard extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?string $productKey = null;

    public array $stats = [];

    public ?string $lastRefreshedAt = null;

    public array $previousStats = [];

    public array $changedStats = [];

    public ?string $importStatus = null;

    protected string $view = 'filament.admin.widgets.product-portfolio-card';

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    /**
     * @return array<string, array{key: string, name: string, description: string, url: string, icon: Heroicon, stats: array<int, array{label: string, value: string}>}>
     */
    public static function getCachedProducts(): array
    {
        $stats = Cache::remember('product_portfolio_stats', 30, fn () => PortfolioSnapshotStats::byProduct());
        $products = [];

        foreach (SamplesProduct::cases() as $product) {
            $products[$product->value] = [
                'key' => $product->value,
                'name' => $product->getLabel(),
                'description' => $product->description(),
                'url' => $product->url(),
                'icon' => $product->icon(),
                'stats' => $stats[$product->value] ?? [],
            ];
        }

        return $products;
    }

    /**
     * @return array<string, array{key: string, name: string, description: string, url: string, icon: Heroicon, stats: array<int, array{label: string, value: string}>}>
     */
    public static function getProducts(): array
    {
        return self::getCachedProducts();
    }

    public function mount(): void
    {
        $this->loadStats();
        $this->loadImportStatus();
    }

    public function refreshStats(): void
    {
        $this->previousStats = $this->stats;
        $this->loadStats();
        $this->detectChangedStats();
        $this->lastRefreshedAt = now()->toIso8601String();
    }

    public function loadStats(): void
    {
        $products = self::getCachedProducts();
        if ($this->productKey !== null && isset($products[$this->productKey])) {
            $this->stats = $products[$this->productKey]['stats'];
        }
    }

    public function detectChangedStats(): void
    {
        $this->changedStats = [];
        foreach ($this->stats as $index => $stat) {
            $previous = $this->previousStats[$index] ?? null;
            if ($previous !== null && $previous['value'] !== $stat['value']) {
                $this->changedStats[] = $index;
            }
        }
    }

    public function loadImportStatus(): void
    {
        $latestRun = ResetRun::where('product', $this->productKey)
            ->latest('created_at')
            ->first();

        $this->importStatus = $latestRun?->status;
    }

    public function refreshStatus(): void
    {
        $previousStatus = $this->importStatus;
        $this->loadImportStatus();
        $this->loadStats();
        $this->lastRefreshedAt = now()->toIso8601String();

        if ($previousStatus === 'running' && $this->importStatus === 'succeeded') {
            Notification::make()
                ->title('Import completed for '.($this->productKey ?? ''))
                ->success()
                ->send();
        }
    }

    public function importDataAction(): Action
    {
        return Action::make('importData')
            ->label('Import Data')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => Gate::allows('product::import'))
            ->requiresConfirmation()
            ->modalHeading('Import '.($this->productKey ?? '').' Data')
            ->modalDescription('This will replace all live '.($this->productKey ?? '').' data with the source baseline.')
            ->modalContent(view('filament.admin.widgets.import-confirmation-detail', [
                'product' => $this->productKey,
            ]))
            ->action(function () {
                if ($this->productKey === null) {
                    return;
                }

                $product = SamplesProduct::tryFrom($this->productKey);
                if ($product === null) {
                    return;
                }

                try {
                    app(ResetWindow::class)->assertWritable($product);
                } catch (ProductResetWindowOpen $e) {
                    Notification::make()
                        ->title('Import blocked')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                ProductImportJob::dispatch($this->productKey);

                Notification::make()
                    ->title('Import started for '.$this->productKey)
                    ->success()
                    ->send();
            });
    }

    /** @return array{product: array|null} */
    protected function getViewData(): array
    {
        $products = self::getProducts();

        $product = $this->productKey !== null && isset($products[$this->productKey])
            ? $products[$this->productKey]
            : null;

        return ['product' => $product];
    }
}
