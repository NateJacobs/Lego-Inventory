<?php

namespace App\Filament\Resources\Themes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('theme.name')
                    ->label('Parent theme')
                    ->placeholder('Top-level')
                    ->sortable(),
                // A top-level theme holds its sets through theme_id and a
                // subtheme through subtheme_id, so show both rather than one
                // column that reads zero for every subtheme.
                TextColumn::make('catalog_items_count')
                    ->label('Sets')
                    ->counts('catalogItems')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subtheme_catalog_items_count')
                    ->label('Sets as subtheme')
                    ->counts('subthemeCatalogItems')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subthemes_count')
                    ->label('Subthemes')
                    ->counts('subthemes')
                    ->badge()
                    ->sortable(),
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
