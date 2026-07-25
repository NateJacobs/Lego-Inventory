<?php

namespace App\Filament\Resources\CollectionLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CollectionLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required(),
                TextInput::make('used_value')
                    ->required()
                    ->numeric(),
                TextInput::make('new_value')
                    ->required()
                    ->numeric(),
                TextInput::make('retail_value')
                    ->required()
                    ->numeric(),
                TextInput::make('total_sets')
                    ->required()
                    ->numeric(),
                TextInput::make('piece_count')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
