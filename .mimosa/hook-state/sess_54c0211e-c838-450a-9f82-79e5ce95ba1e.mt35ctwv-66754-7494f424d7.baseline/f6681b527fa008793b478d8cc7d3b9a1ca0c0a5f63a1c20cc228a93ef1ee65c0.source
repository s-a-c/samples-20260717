<?php

declare(strict_types=1);

namespace App\Filament\Pagila\Resources;

use App\Filament\Pagila\Resources\RentalResource\Pages;
use App\Models\Pagila\Rental;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

final class RentalResource extends Resource
{
    protected static ?string $model = Rental::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pagila';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\DateTimePicker::make('rental_date')
                    ->required(),
                Forms\Components\Select::make('inventory_id')
                    ->relationship('inventory', 'id')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'last_name')
                    ->searchable()
                    ->required(),
                Forms\Components\DateTimePicker::make('return_date'),
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'last_name')
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rental_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.last_name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('return_date')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListRentals::route('/'),
            'create' => Pages\CreateRental::route('/create'),
            'edit' => Pages\EditRental::route('/{record}/edit'),
        ];
    }
}
