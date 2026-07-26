<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Chinook\Album;
use App\Models\Chinook\Artist;
use App\Models\Chinook\Customer;
use App\Models\Chinook\Track;
use App\Models\Northwind\Category;
use App\Models\Northwind\Product;
use App\Models\Northwind\Supplier;
use App\Models\Pagila\Actor;
use App\Models\Pagila\Film;
use App\Models\User;
use App\Observers\Tier1SourceObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        $this->loadMigrationsFrom([
            database_path('migrations/chinook'),
            database_path('migrations/northwind'),
            database_path('migrations/pagila'),
        ]);

        $this->registerObservers();

        $this->configureDefaults();
    }

    /**
     * Register model observers for search projection embedding jobs.
     */
    protected function registerObservers(): void
    {
        Artist::observe(Tier1SourceObserver::class);
        Album::observe(Tier1SourceObserver::class);
        Track::observe(Tier1SourceObserver::class);
        Customer::observe(Tier1SourceObserver::class);

        Product::observe(Tier1SourceObserver::class);
        Category::observe(Tier1SourceObserver::class);
        Customer::observe(Tier1SourceObserver::class);
        Supplier::observe(Tier1SourceObserver::class);

        Film::observe(Tier1SourceObserver::class);
        Actor::observe(Tier1SourceObserver::class);
        Category::observe(Tier1SourceObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
