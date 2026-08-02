<?php

namespace App\Filament\Resources\BulkBricks\Tables;

use App\Models\BulkBrick;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BulkBricksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('acquired_date')
                    ->label('Acquired')
                    ->date()
                    ->sortable(),
                TextColumn::make('acquiredLocation.name')
                    ->label('Acquired from')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unknown'),
                TextColumn::make('piece_count')
                    ->label('Pieces')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('value')
                    ->money('usd')
                    ->sortable(),
                // What the lot worked out to per piece — the figure that says
                // whether a bulk buy was actually a good one.
                TextColumn::make('cost_per_piece')
                    ->label('Per piece')
                    ->state(fn (BulkBrick $record): ?string => $record->piece_count
                        ? '$'.number_format($record->cost / $record->piece_count, 3)
                        : null)
                    ->placeholder('—'),
                TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('acquired_date', 'desc')
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
