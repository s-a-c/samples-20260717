<?php

namespace App\Filament\Chinook\Resources;

use App\Domain\Chinook\Models\Track;
use App\Filament\Chinook\Resources\TrackResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TrackResource extends Resource
{
    protected static ?string $model = Track::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-musical-note';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('album_id')
                    ->relationship('album', 'title')
                    ->searchable(),
                Forms\Components\Select::make('media_type_id')
                    ->relationship('mediaType', 'name')
                    ->required(),
                Forms\Components\Select::make('genre_id')
                    ->relationship('genre', 'name')
                    ->searchable(),
                Forms\Components\TextInput::make('composer')
                    ->maxLength(255),
                Forms\Components\TextInput::make('milliseconds')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('bytes')
                    ->numeric(),
                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('album.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('genre.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('composer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('milliseconds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money()
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
            'index' => Pages\ListTracks::route('/'),
            'create' => Pages\CreateTrack::route('/create'),
            'edit' => Pages\EditTrack::route('/{record}/edit'),
        ];
    }
}
