<?php

namespace App\Filament\Resources\StorageLocations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StorageLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('city')
                    ->default(null),
                TextInput::make('state')
                    ->default(null),
                TextInput::make('zip_code')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
