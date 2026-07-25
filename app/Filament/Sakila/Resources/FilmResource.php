<?php

namespace App\Filament\Sakila\Resources;

use App\Domain\Sakila\Models\Film;
use App\Filament\Sakila\Resources\FilmResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FilmResource extends Resource
{
    protected static ?string $model = Film::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sakila';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-film';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('release_year')
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(2100),
                Forms\Components\Select::make('language_id')
                    ->relationship('language', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('original_language_id')
                    ->relationship('originalLanguage', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\TextInput::make('rental_duration')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('rental_rate')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('length')
                    ->numeric()
                    ->suffix('min'),
                Forms\Components\TextInput::make('replacement_cost')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('rating')
                    ->maxLength(10),
                Forms\Components\Textarea::make('special_features')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('language.name')
                    ->label('Language')
                    ->sortable(),
                Tables\Columns\TextColumn::make('release_year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rental_rate')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('length')
                    ->sortable()
                    ->suffix(' min'),
                Tables\Columns\TextColumn::make('rating')
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
            'index' => Pages\ListFilms::route('/'),
            'create' => Pages\CreateFilm::route('/create'),
            'edit' => Pages\EditFilm::route('/{record}/edit'),
        ];
    }
}
