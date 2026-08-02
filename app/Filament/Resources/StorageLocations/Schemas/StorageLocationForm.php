<?php

namespace App\Filament\Resources\StorageLocations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StorageLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        // All four columns are NOT NULL, so leaving the address blank had the
        // database reject the record rather than saving a partial location.
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('city')
                    ->maxLength(100)
                    ->required(),
                TextInput::make('state')
                    ->maxLength(15)
                    ->required(),
                TextInput::make('zip_code')
                    ->numeric()
                    ->required(),
            ]);
    }
}
