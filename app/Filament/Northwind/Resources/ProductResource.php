<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources;

use App\Filament\Northwind\Resources\ProductResource\Pages;
use App\Models\Northwind\Product;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Override;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('product_name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'company_name')
                ->searchable()
                ->nullable(),
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'category_name')
                ->searchable()
                ->nullable(),
            Forms\Components\TextInput::make('quantity_per_unit')
                ->maxLength(255),
            Forms\Components\TextInput::make('unit_price')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\TextInput::make('units_in_stock')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('units_on_order')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('reorder_level')
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('discontinued'),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('product_name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('supplier.company_name')
                ->label('Supplier')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('category.category_name')
                ->label('Category')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('unit_price')
                ->money()
                ->sortable(),
            Tables\Columns\TextColumn::make('units_in_stock')
                ->sortable(),
            Tables\Columns\IconColumn::make('discontinued')
                ->boolean()
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

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
