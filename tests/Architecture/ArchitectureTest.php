<?php

declare(strict_types=1);

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// ─── Domain Model Rules ───────────────────────────────────────────────────────

arch('Domain models must use BelongsToProductDomain trait')
    ->expect('App\Models\*')
    ->toUseTrait(BelongsToProductDomain::class);

arch('Domain models must use HasUuids trait')
    ->expect('App\Models\*')
    ->toUseTrait(HasUuids::class);

arch('Domain models must be final')
    ->expect('App\Models\*')
    ->toBeFinal();

arch('Domain models must have #[Table] attribute')
    ->expect('App\Models\*')
    ->toHaveAttribute(Table::class);

// ─── Product Namespace Rules ───────────────────────────────────────────────────

arch('Chinook models must use correct namespace')
    ->expect('App\Models\Chinook')
    ->toUseTrait(BelongsToProductDomain::class);

arch('Northwind models must use correct namespace')
    ->expect('App\Models\Northwind')
    ->toUseTrait(BelongsToProductDomain::class);

arch('Pagila models must use correct namespace')
    ->expect('App\Models\Pagila')
    ->toUseTrait(BelongsToProductDomain::class);

// ─── Reset Ownership Rules ────────────────────────────────────────────────────

arch('ResetWindow must be sole reader of reset_runs')
    ->expect('App\Services\ProductReset\ResetWindow')
    ->not->toUse('App\Services\ProductReset\ResetConfirmationService')
    ->not->toUse('App\Services\ProductReset\RecoveryService');

arch('ResetConfirmationService must be exclusive writer to reset_confirmations')
    ->expect('App\Services\ProductReset\ResetConfirmationService')
    ->not->toUse('App\Services\ProductReset\ResetWindow');

arch('RecoveryService must own transition logic')
    ->expect('App\Services\ProductReset\RecoveryService')
    ->toBeFinal();

// ─── Import Isolation Rules ────────────────────────────────────────────────────

arch('Chinook importer must not reference sibling product namespaces')
    ->expect('App\Services\ProductImport\ChinookImporter')
    ->not->toUse(['App\Models\Northwind', 'App\Models\Pagila', 'App\Filament\Northwind', 'App\Filament\Pagila']);

arch('Northwind importer must not reference sibling product namespaces')
    ->expect('App\Services\ProductImport\NorthwindImporter')
    ->not->toUse(['App\Models\Chinook', 'App\Models\Pagila', 'App\Filament\Chinook', 'App\Filament\Pagila']);

arch('Pagila importer must not reference sibling product namespaces')
    ->expect('App\Services\ProductImport\PagilaImporter')
    ->not->toUse(['App\Models\Chinook', 'App\Models\Northwind', 'App\Filament\Chinook', 'App\Filament\Northwind']);

// ─── Presentation Isolation Rules ───────────────────────────────────────────────

arch('Http layer must not use DB facade')
    ->expect('App\Http\**')
    ->not->toUse(DB::class);

arch('Filament layer must not use DB facade')
    ->expect('App\Filament\**')
    ->not->toUse(DB::class);

arch('Http layer must not use service locator')
    ->expect('App\Http\**')
    ->not->toUse(Application::class);

arch('Filament layer must not use service locator')
    ->expect('App\Filament\**')
    ->not->toUse(Application::class);

// ─── Filament Resource Rules ──────────────────────────────────────────────────

arch('Chinook resources must extend Resource')
    ->expect('App\Filament\Chinook\Resources')
    ->toExtend('Filament\Resources\Resource')
    ->ignoring('App\Filament\Chinook\Resources\AlbumResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\ArtistResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\CustomerResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\EmployeeResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\GenreResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\InvoiceResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\PlaylistResource\Pages')
    ->ignoring('App\Filament\Chinook\Resources\TrackResource\Pages');

arch('Pagila resources must extend Resource')
    ->expect('App\Filament\Pagila\Resources')
    ->toExtend('Filament\Resources\Resource')
    ->ignoring('App\Filament\Pagila\Resources\ActorResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\CategoryResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\CustomerResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\FilmResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\InventoryResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\LanguageResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\PaymentResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\RentalResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\StaffResource\Pages')
    ->ignoring('App\Filament\Pagila\Resources\StoreResource\Pages');

arch('Northwind resources must extend Resource')
    ->expect('App\Filament\Northwind\Resources')
    ->toExtend('Filament\Resources\Resource')
    ->ignoring('App\Filament\Northwind\Resources\CategoryResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\CustomerResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\EmployeeResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\OrderResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\ProductResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\ShipperResource\Pages')
    ->ignoring('App\Filament\Northwind\Resources\SupplierResource\Pages');

// ─── Panel Provider & Service Rules ───────────────────────────────────────────

arch('Filament panel providers must be final')
    ->expect('App\Providers\Filament')
    ->toBeFinal();

arch('Services must be final')
    ->expect('App\Services\**')
    ->toBeFinal()
    ->ignoring('App\Services\ProductImport\PostgresSourceReader')
    ->ignoring('App\Services\ProductImport\ChinookImporter')
    ->ignoring('App\Services\ProductImport\NorthwindImporter')
    ->ignoring('App\Services\ProductImport\PagilaImporter')
    ->ignoring('App\Services\ProductImport\ProductImportPipeline');

// ─── Debug / Prohibited Function Rules ────────────────────────────────────────

arch('No dd or dump calls in application code')
    ->expect('App')
    ->not->toUse(['dd', 'dump']);

arch('No env() calls in application code')
    ->expect('App')
    ->not->toUse('env');
