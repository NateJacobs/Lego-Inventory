<?php

namespace App\Filament\Resources\AcquiredLocations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AcquiredLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
