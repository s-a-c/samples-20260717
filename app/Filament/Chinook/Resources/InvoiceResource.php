<?php

namespace App\Filament\Chinook\Resources;

use App\Domain\Chinook\Models\Invoice;
use App\Filament\Chinook\Resources\InvoiceResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'last_name')
                    ->searchable()
                    ->required(),
                Forms\Components\DateTimePicker::make('invoice_date')
                    ->required(),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('billing_address')
                    ->maxLength(255),
                Forms\Components\TextInput::make('billing_city')
                    ->maxLength(255),
                Forms\Components\TextInput::make('billing_state')
                    ->maxLength(255),
                Forms\Components\TextInput::make('billing_country')
                    ->maxLength(255),
                Forms\Components\TextInput::make('billing_postal_code')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_country')
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
