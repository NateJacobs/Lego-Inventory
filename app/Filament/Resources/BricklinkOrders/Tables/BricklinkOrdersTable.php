<?php

namespace App\Filament\Resources\BricklinkOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BricklinkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('seller_name')
                    ->searchable(),
                TextColumn::make('store_name')
                    ->searchable(),
                TextColumn::make('order_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pieces')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('order_cost')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('shipping_cost')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_cost')
                    ->label('Total cost')
                    ->money('usd')
                    ->description('Order + shipping'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
