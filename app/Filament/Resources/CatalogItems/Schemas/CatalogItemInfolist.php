<?php

namespace App\Filament\Resources\CatalogItems\Schemas;

use App\Models\CatalogItem;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CatalogItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Set')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label('')
                            ->height(180)
                            ->extraImgAttributes(['class' => 'object-contain'])
                            ->columnSpan(1),

                        // The identifying details, beside the picture.
                        Section::make()
                            ->columns(2)
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('set_number')
                                    ->label('Set number')
                                    ->formatStateUsing(fn (CatalogItem $record): string => $record->set_number_variant > 1
                                        ? "{$record->set_number}-{$record->set_number_variant}"
                                        : (string) $record->set_number)
                                    ->weight('bold')
                                    ->copyable(),
                                TextEntry::make('name'),
                                TextEntry::make('year')
                                    ->placeholder('Unknown'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->placeholder('—'),
                                TextEntry::make('theme.name')
                                    ->label('Theme')
                                    ->placeholder('Unassigned'),
                                TextEntry::make('subtheme.name')
                                    ->label('Subtheme')
                                    ->placeholder('None'),
                            ]),
                    ]),

                Section::make('Contents')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('piece_count')
                            ->label('Pieces')
                            ->numeric()
                            ->placeholder('Unknown'),
                        TextEntry::make('minifig_count')
                            ->label('Minifigures')
                            ->numeric()
                            ->placeholder('None'),
                        TextEntry::make('theme_group')
                            ->label('Theme group')
                            ->placeholder('—'),
                    ]),

                Section::make('Value')
                    ->columns(3)
                    ->description('BrickLink prices are refreshed by the monthly collection:refresh-prices batch.')
                    ->schema([
                        TextEntry::make('retail_price')
                            ->label('MSRP')
                            ->money('usd')
                            ->placeholder('—'),
                        TextEntry::make('current_value_new')
                            ->label('BrickLink new')
                            ->money('usd')
                            ->placeholder('Not priced'),
                        TextEntry::make('current_value_used')
                            ->label('BrickLink used')
                            ->money('usd')
                            ->placeholder('Not priced'),

                        // What the owned copies of this set are worth in total,
                        // rather than the per-copy figures above.
                        TextEntry::make('owned')
                            ->label('Copies owned')
                            ->badge()
                            ->state(fn (CatalogItem $record): int => $record->sets()->count()),
                        TextEntry::make('owned_value_new')
                            ->label('Owned value (new)')
                            ->money('usd')
                            ->state(fn (CatalogItem $record): float => $record->sets()->count() * (float) $record->current_value_new),
                        TextEntry::make('owned_value_used')
                            ->label('Owned value (used)')
                            ->money('usd')
                            ->state(fn (CatalogItem $record): float => $record->sets()->count() * (float) $record->current_value_used),
                    ]),

                Section::make('References')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('brickset_url')
                            ->label('Brickset')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('bricklink_id')
                            ->label('BrickLink ID')
                            ->placeholder('—'),
                        TextEntry::make('brickset_id')
                            ->label('Brickset ID')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Added')
                            ->dateTime(),
                    ]),
            ]);
    }
}
