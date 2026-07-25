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
                    ->required(),
                TextInput::make('order_number')
                    ->required()
                    ->numeric(),
                TextInput::make('pieces')
                    ->required()
                    ->numeric(),
                TextInput::make('order_cost')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
