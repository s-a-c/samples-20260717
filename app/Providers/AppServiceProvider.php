<?php

namespace App\Providers;

use App\Domain\Chinook\Models\Album;
use App\Domain\Chinook\Models\Artist;
use App\Domain\Chinook\Models\Customer;
use App\Domain\Chinook\Models\Track;
use App\Domain\Northwind\Models\Category;
use App\Domain\Northwind\Models\Product;
use App\Domain\Northwind\Models\Supplier;
use App\Domain\Pagila\Models\Actor;
use App\Domain\Pagila\Models\Film;
use App\Models\User;
use App\Observers\Tier1SourceObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
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
        \App\Domain\Northwind\Models\Customer::observe(Tier1SourceObserver::class);
        Supplier::observe(Tier1SourceObserver::class);

        Film::observe(Tier1SourceObserver::class);
        Actor::observe(Tier1SourceObserver::class);
        \App\Domain\Pagila\Models\Category::observe(Tier1SourceObserver::class);
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
