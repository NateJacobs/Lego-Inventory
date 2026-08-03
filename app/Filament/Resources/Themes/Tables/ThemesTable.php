<?php

namespace App\Filament\Resources\Themes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSetCounts())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('theme.name')
                    ->label('Parent theme')
                    ->placeholder('Top-level')
                    ->sortable(),
                TextColumn::make('subthemes_count')
                    ->label('Subthemes')
                    ->badge()
                    ->sortable(),

                // Sets sit on their most specific theme, so a top-level theme's
                // own count leaves out everything under its subthemes. Lead
                // with the total and keep the direct figure available.
                TextColumn::make('total_catalog_items_count')
                    ->label('Sets')
                    ->tooltip('Includes sets filed under this theme’s subthemes')
                    ->badge()
                    ->sortable(),
                TextColumn::make('catalog_items_count')
                    ->label('Sets (this theme only)')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
