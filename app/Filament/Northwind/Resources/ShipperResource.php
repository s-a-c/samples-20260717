<?php

declare(strict_types=1);

namespace App\Filament\Northwind\Resources;

use App\Filament\Northwind\Resources\ShipperResource\Pages;
use App\Models\Northwind\Shipper;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Override;

final class ShipperResource extends Resource
{
    protected static ?string $model = Shipper::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('company_name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->maxLength(255),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company_name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('phone')
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

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippers::route('/'),
            'create' => Pages\CreateShipper::route('/create'),
            'edit' => Pages\EditShipper::route('/{record}/edit'),
        ];
    }
}
