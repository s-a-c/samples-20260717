<?php

declare(strict_types=1);

use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// ─── Domain Model Rules ───────────────────────────────────────────────────────

arch('Domain models must use BelongsToProductDomain trait')
    ->expect('App\Domain\*\Models')
    ->toUseTrait(BelongsToProductDomain::class);

arch('Domain models must use HasUuids trait')
    ->expect('App\Domain\*\Models')
    ->toUseTrait(HasUuids::class);

arch('Domain models must be final')
    ->expect('App\Domain\*\Models')
    ->toBeFinal();

arch('Domain models must have #[Table] attribute')
    ->expect('App\Domain\*\Models')
    ->toHaveAttribute(Table::class);

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

// ─── Panel Provider & Service Rules ───────────────────────────────────────────

arch('Filament panel providers must be final')
    ->expect('App\Providers\Filament')
    ->toBeFinal();

arch('Services must be final')
    ->expect('App\Services\**')
    ->toBeFinal();

// ─── Debug / Prohibited Function Rules ────────────────────────────────────────

arch('No dd or dump calls in application code')
    ->expect('App')
    ->not->toUse(['dd', 'dump']);

arch('No env() calls in application code')
    ->expect('App')
    ->not->toUse('env');
