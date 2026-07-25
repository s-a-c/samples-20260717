<?php

namespace App\Filament\Pagila\Resources;

use App\Domain\Pagila\Models\Inventory;
use App\Filament\Pagila\Resources\InventoryResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Pagila';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('film_id')
                    ->relationship('film', 'title')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('store_id')
                    ->relationship('store', 'id')
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('film.title')
                    ->label('Film')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.id')
                    ->label('Store')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
