<?php

namespace App\Filament\Resources\CatalogItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CatalogItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // The only fields a user enters; the CatalogItemObserver fills
                // the rest from Brickset + BrickLink when the record is created.
                Section::make('Set')
                    ->columns(3)
                    ->schema([
                        TextInput::make('set_number')
                            ->required(),
                        TextInput::make('set_number_variant')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Select::make('type')
                            ->options(['set' => 'Set', 'book' => 'Book', 'gear' => 'Gear'])
                            ->default('set')
                            ->required(),
                    ]),

                // Enriched, observer-managed data — visible for reference on
                // edit, but not user-editable.
                Section::make('Details')
                    ->columns(2)
                    ->visibleOn('edit')
                    ->schema([
                        TextInput::make('name')->disabled(),
                        TextInput::make('year')->disabled(),
                        TextInput::make('piece_count')->numeric()->disabled(),
                        TextInput::make('minifig_count')->numeric()->disabled(),
                        Select::make('theme_id')->label('Theme')
                            ->relationship('theme', 'name')->disabled(),
                        Select::make('subtheme_id')->label('Subtheme')
                            ->relationship('subtheme', 'name')->disabled(),
                        TextInput::make('retail_price')->prefix('$')->disabled(),
                        TextInput::make('current_value_new')->label('Current value (new)')->prefix('$')->disabled(),
                        TextInput::make('current_value_used')->label('Current value (used)')->prefix('$')->disabled(),
                        TextInput::make('brickset_url')->label('Brickset URL')->url()->disabled()->columnSpanFull(),
                    ]),
            ]);
    }
}
