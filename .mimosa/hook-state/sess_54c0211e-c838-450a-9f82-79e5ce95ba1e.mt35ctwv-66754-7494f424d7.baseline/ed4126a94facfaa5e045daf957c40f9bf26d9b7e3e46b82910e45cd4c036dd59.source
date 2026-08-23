<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources;

use App\Filament\Pagila\Resources\StoreResource\Pages;
use App\Models\Pagila\Store;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

final class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pagila';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('manager_staff_id')
                    ->relationship('manager', 'last_name')
                    ->searchable(),
                Forms\Components\Select::make('address_id')
                    ->relationship('address', 'address')
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('manager.last_name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.address')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
