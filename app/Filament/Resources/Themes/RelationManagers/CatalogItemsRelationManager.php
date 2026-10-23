<?php

namespace App\Filament\Resources\Themes\RelationManagers;

use App\Filament\Resources\CatalogItems\CatalogItemResource;
use App\Models\CatalogItem;
use App\Models\Theme;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CatalogItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'catalogItems';

    protected static ?string $title = 'Sets';

    public function table(Table $table): Table
    {
        $theme = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            // A set records its most specific theme, so the relationship alone
            // holds only what Brickset files directly here — for a top-level
            // theme that is usually nothing at all, while its subthemes hold
            // everything. Widen it to the subthemes so the page answers "what
            // is in this theme" rather than "what escaped subthemeing".
            //
            // Safe as an ungrouped orWhere because a relation manager only ever
            // queries the one owner record; the relation on Theme is left alone
            // so eager loading elsewhere stays correct.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('theme')
                ->orWhereIn('theme_id', Theme::query()
                    ->select('id')
                    ->where('parent_id', $theme->getKey())))
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('set_number')
                    ->label('Set')
                    ->formatStateUsing(fn (CatalogItem $record): string => $record->item_number)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->limit(40),

                // Which subtheme a set came in under, so a top-level theme's
                // list is readable rather than a flat heap.
                TextColumn::make('theme.name')
                    ->label('Subtheme')
                    ->placeholder('Filed directly here')
                    ->sortable()
                    ->visible(fn (): bool => $theme->parent_id === null),
                TextColumn::make('year')
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
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('current_value_new')
                    ->label('New')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('set_number')
            ->recordActions([
                // Opens the set as a record in its own right, as the subthemes
                // table does.
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (CatalogItem $record): string => CatalogItemResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
