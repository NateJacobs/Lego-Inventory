<?php

namespace App\Filament\Resources\Sets\Tables;

use App\Models\Set;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('catalogItem.thumbnail_path')
                    ->label('')
                    ->square(),
                TextColumn::make('catalogItem.set_number')
                    ->label('Set')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('catalogItem.name')
                    ->label('Name')
                    ->searchable()
                    ->limit(36),
                TextColumn::make('current_condition')
                    ->badge()
                    ->sortable(),
                TextColumn::make('catalogItem.retail_price')
                    ->label('MSRP')
                    ->money('usd'),
                TextColumn::make('purchase_price')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('savings')
                    ->label('Savings')
                    ->state(function (Set $record): ?string {
                        $retail = $record->catalogItem?->retail_price;
                        if (! $retail || $record->purchase_price === null) {
                            return null;
                        }

                        return round((($retail - $record->purchase_price) / $retail) * 100, 1).'%';
                    }),
                TextColumn::make('storageLocation.name')
                    ->label('Storage')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('acquiredLocation.name')
                    ->label('Acquired from')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
