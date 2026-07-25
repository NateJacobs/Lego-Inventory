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
        return $schema
            ->components([
                TextInput::make('type'),
                TextInput::make('piece_count')
                    ->label('Pieces')
                    ->numeric(),
                TextInput::make('cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('value')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                DatePicker::make('acquired_date'),
                Select::make('acquired_location_id')
                    ->label('Acquired from')
                    ->relationship('acquiredLocation', 'name')
                    ->searchable(),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
