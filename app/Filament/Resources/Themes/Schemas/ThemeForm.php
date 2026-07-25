<?php

namespace App\Filament\Resources\Themes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent theme')
                    ->relationship('theme', 'name')
                    ->searchable()
                    ->placeholder('None (top-level theme)'),
            ]);
    }
}
