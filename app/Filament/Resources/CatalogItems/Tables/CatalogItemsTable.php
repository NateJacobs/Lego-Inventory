<?php

namespace App\Filament\Resources\CatalogItems\Tables;

use App\Exceptions\BricklinkPriceException;
use App\Exceptions\BricksetImageException;
use App\Exceptions\BricksetLookupException;
use App\Models\CatalogItem;
use App\Models\Theme;
use App\UpdateCatalogItem;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                // The observer writes set images to the public disk, which
                // is not the disk Filament reaches for by default.
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->disk('public')
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
            ->filters([
                SelectFilter::make('theme')
                    ->label('Theme')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::themeOptions())
                    ->query(fn (Builder $query, array $data): Builder => self::filterByTheme($query, $data)),
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
                                    // Not bricklink_id: it is an empty string
                                    // until a lookup succeeds, which listed
                                    // failures as blanks.
                                    $failed[] = $record->item_number;
                                }
                            }

                            $notification = Notification::make()->title("Updated {$updated} set(s).");

                            empty($failed)
                                ? $notification->success()
                                : $notification->body('Failed: '.implode(', ', $failed))->warning();

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('refreshBricksetDetails')
                        ->label('Refresh Brickset details')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->requiresConfirmation()
                        ->modalDescription('Name, year, piece and minifigure counts, MSRP and theme will be replaced with what Brickset holds now.')
                        ->action(function (Collection $records) {
                            $refreshed = 0;
                            $failed = [];

                            foreach ($records as $record) {
                                try {
                                    (new UpdateCatalogItem($record))->updateDetails();
                                    $refreshed++;
                                } catch (BricksetLookupException $e) {
                                    $failed[] = $record->item_number;
                                }
                            }

                            $notification = Notification::make()
                                ->title("Refreshed {$refreshed} set(s) from Brickset.");

                            empty($failed)
                                ? $notification->success()
                                : $notification->body('Failed: '.implode(', ', $failed))->warning();

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('downloadImages')
                        ->label('Download images')
                        ->icon('heroicon-o-photo')
                        ->action(function (Collection $records) {
                            $downloaded = 0;
                            $alreadyThere = 0;
                            $failed = [];

                            foreach ($records as $record) {
                                try {
                                    (new UpdateCatalogItem($record))->updateImages()
                                        ? $downloaded++
                                        : $alreadyThere++;
                                } catch (BricksetImageException|BricksetLookupException $e) {
                                    $failed[] = $record->item_number;
                                }
                            }

                            $notification = Notification::make()
                                ->title("Downloaded images for {$downloaded} set(s).");

                            $body = [];

                            if ($alreadyThere > 0) {
                                $body[] = "{$alreadyThere} already had both files.";
                            }

                            if (! empty($failed)) {
                                $body[] = 'Failed: '.implode(', ', $failed);
                            }

                            empty($failed)
                                ? $notification->success()
                                : $notification->warning();

                            $notification->body(implode(' ', $body))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Themes to offer, labelled by where they sit: "Harry Potter" for the
     * top-level theme, "Harry Potter › General" for a subtheme.
     *
     * Only themes that would actually match something. Brickset's tree holds
     * 1,116 themes and barely a third of them appear in this collection, so
     * listing all of them would be mostly dead ends. A top-level theme counts
     * as used when one of its subthemes is.
     *
     * @return array<int, string>
     */
    protected static function themeOptions(): array
    {
        $used = CatalogItem::query()->distinct()->whereNotNull('theme_id')->pluck('theme_id');

        return Theme::query()
            ->with('theme')
            ->where(fn (Builder $themes): Builder => $themes
                ->whereIn('id', $used)
                ->orWhereIn('id', Theme::query()
                    ->select('parent_id')
                    ->whereIn('id', $used)
                    ->whereNotNull('parent_id')))
            ->get()
            // Each top-level theme, then its own subthemes beneath it.
            ->sortBy(fn (Theme $theme): array => [
                $theme->parent_id ? (string) $theme->theme?->name : $theme->name,
                $theme->parent_id ? 1 : 0,
                $theme->name,
            ])
            ->mapWithKeys(fn (Theme $theme): array => [$theme->id => $theme->full_name])
            ->all();
    }

    /**
     * A set records its most specific theme, so picking a top-level theme has
     * to reach the subthemes under it as well — otherwise choosing "Harry
     * Potter" would show only the handful of sets Brickset files directly in
     * it. Picking a subtheme matches that subtheme alone.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function filterByTheme(Builder $query, array $data): Builder
    {
        $ids = array_filter((array) ($data['values'] ?? []));

        if (empty($ids)) {
            return $query;
        }

        // One subquery for the children rather than a lookup per selection.
        return $query->where(fn (Builder $items): Builder => $items
            ->whereIn('theme_id', $ids)
            ->orWhereIn('theme_id', Theme::query()->select('id')->whereIn('parent_id', $ids)));
    }
}
