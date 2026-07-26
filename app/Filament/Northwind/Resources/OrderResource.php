<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources;

use App\Filament\Northwind\Resources\OrderResource\Pages;
use App\Models\Northwind\Order;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'company_name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'last_name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\DateTimePicker::make('order_date'),
                Forms\Components\DateTimePicker::make('required_date'),
                Forms\Components\DateTimePicker::make('shipped_date'),
                Forms\Components\Select::make('ship_via')
                    ->relationship('shipper', 'company_name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\TextInput::make('freight')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('ship_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ship_address')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ship_city')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ship_region')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ship_postal_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ship_country')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.last_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('required_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shipped_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('freight')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ship_country')
                    ->searchable()
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
