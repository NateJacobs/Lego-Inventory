<?php

namespace App\Filament\Resources\Sets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_item_id')
                    ->label('Set')
                    ->relationship('catalogItem', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('current_condition')
                    ->options([
                        'parted out' => 'Parted Out',
                        'assembled' => 'Assembled',
                        'misb' => 'MISB',
                    ])
                    ->required(),
                // Both are nullable: older copies predate ever recording where
                // they live or where they came from.
                Select::make('storage_location_id')
                    ->label('Storage location')
                    ->relationship('storageLocation', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('acquired_location_id')
                    ->label('Acquired from')
                    ->relationship('acquiredLocation', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('purchase_price')
                    ->numeric()
                    ->prefix('$'),
                DatePicker::make('purchase_date'),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
