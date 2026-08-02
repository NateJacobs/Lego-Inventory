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
                DatePicker::make('date'),
                TextInput::make('used_value')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('new_value')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                // Nullable: the oldest snapshots never recorded a retail total.
                TextInput::make('retail_value')
                    ->numeric()
                    ->prefix('$'),
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
