<?php

namespace App\Filament\Resources\CatalogItems\Tables;

use App\Exceptions\BricklinkPriceException;
use App\Models\CatalogItem;
use App\UpdateCatalogItem;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CatalogItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // root_theme walks up from the subtheme, so load both levels once.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('theme.theme'))
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->square(),
                TextColumn::make('set_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('year')
                    ->sortable(),
                // theme_id points at the subtheme when a set has one, so show
                // the top-level theme above it for context.
                TextColumn::make('theme.name')
                    ->label('Theme')
                    ->description(fn (CatalogItem $record): ?string => $record->subtheme
                        ? $record->root_theme?->name
                        : null, position: 'above')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('piece_count')
                    ->label('Pieces')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sets_count')
                    ->label('Owned')
                    ->counts('sets')
                    ->badge()
                    ->sortable(),
                TextColumn::make('retail_price')
                    ->label('MSRP')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('current_value_new')
                    ->label('New')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('current_value_used')
                    ->label('Used')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('set_number')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('updateBricklinkPrices')
                        ->label('Update BrickLink prices')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Collection $records) {
                            $updated = 0;
                            $failed = [];

                            foreach ($records as $record) {
                                try {
                                    (new UpdateCatalogItem($record))->updateBricklink();
                                    $updated++;
                                } catch (BricklinkPriceException $e) {
                                    $failed[] = $record->bricklink_id ?? $record->set_number;
                                }
                            }

                            $notification = Notification::make()->title("Updated {$updated} set(s).");

                            empty($failed)
                                ? $notification->success()
                                : $notification->body('Failed: '.implode(', ', $failed))->warning();

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
