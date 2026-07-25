<?php

namespace App\Filament\Resources\CollectionLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('new_value')
                    ->label('New')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('used_value')
                    ->label('Used')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('retail_value')
                    ->label('Retail')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('total_sets')
                    ->label('Sets')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('piece_count')
                    ->label('Pieces')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
