<?php

namespace App\Filament\Resources\BulkBricks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BulkBrickForm
{
    public static function configure(Schema $schema): Schema
    {
        // A bulk lot is entered entirely by hand and every column but the note
        // is NOT NULL, so all of them are required here.
        return $schema
            ->components([
                Select::make('acquired_location_id')
                    ->label('Acquired from')
                    ->relationship('acquiredLocation', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('acquired_date')
                    ->required(),
                TextInput::make('piece_count')
                    ->label('Pieces')
                    ->numeric()
                    ->required(),
                TextInput::make('cost')
                    ->label('Cost')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                TextInput::make('value')
                    ->label('Value')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
