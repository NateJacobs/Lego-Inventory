<?php

namespace App\Filament\Resources\Themes\Schemas;

use App\Models\Theme;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ThemeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name')
                            ->weight('bold'),
                        TextEntry::make('theme.name')
                            ->label('Parent theme')
                            ->placeholder('Top-level theme'),
                        TextEntry::make('subthemes_count')
                            ->label('Subthemes')
                            ->badge()
                            ->state(fn (Theme $record): int => $record->subthemes()->count()),

                        // Sets sit on their most specific theme, so the total
                        // has to reach down into the subthemes listed below.
                        TextEntry::make('sets_count')
                            ->label('Sets')
                            ->badge()
                            ->state(fn (Theme $record): int => $record->totalCatalogItemsCount()),
                        TextEntry::make('direct_sets_count')
                            ->label('Filed directly here')
                            ->badge()
                            ->state(fn (Theme $record): int => $record->catalogItems()->count())
                            ->visible(fn (Theme $record): bool => $record->subthemes()->exists()),
                    ]),
            ]);
    }
}
