<?php

namespace App\Filament\Resources\BricklinkOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BricklinkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('purchase_date')
                    ->required(),
                TextInput::make('seller_name')
                    ->required(),
                TextInput::make('store_name')
                    ->helperText('Blank for a seller without a storefront.'),
                TextInput::make('order_number')
                    ->required()
                    ->numeric(),
                TextInput::make('pieces')
                    ->required()
                    ->numeric(),
                // The breakdown is optional — older orders only ever recorded a
                // total — but the total itself is what the collection value uses.
                TextInput::make('order_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('shipping_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('total_cost')
                    ->label('Total paid')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
