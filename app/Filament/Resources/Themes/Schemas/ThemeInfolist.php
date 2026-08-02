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

                        // A top-level theme holds its sets through theme_id; a
                        // subtheme holds them through subtheme_id. Read whichever
                        // applies so the count is never a misleading zero.
                        TextEntry::make('sets_count')
                            ->label('Sets')
                            ->badge()
                            ->state(fn (Theme $record): int => $record->parent_id === null
                                ? $record->catalogItems()->count()
                                : $record->subthemeCatalogItems()->count()),
                    ]),
            ]);
    }
}
