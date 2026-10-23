<?php

namespace App\Filament\Resources\Themes\RelationManagers;

use App\Filament\Resources\Themes\ThemeResource;
use App\Models\Theme;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubthemesRelationManager extends RelationManager
{
    protected static string $relationship = 'subthemes';

    /**
     * Themes are only ever two deep, so a subtheme has no subthemes of its own
     * — it shows its parent's instead, which are its siblings.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return $ownerRecord->parent_id === null
            ? 'Subthemes'
            : 'Subthemes of '.($ownerRecord->theme?->name ?? 'this theme');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Whether this page is showing a subtheme's siblings rather than a
     * top-level theme's own children. Editing from there is deliberately off:
     * the table is for finding your way across a theme, and a subtheme created
     * from a subtheme's page would hang three levels deep.
     */
    protected function showsSiblings(): bool
    {
        return $this->getOwnerRecord()->parent_id !== null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        $theme = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            // The relationship holds the owner's own children, which a subtheme
            // never has. Reach its parent's instead, so a subtheme page lists
            // the siblings it sits among — the current one included, since the
            // set counts beside it are the reason to look.
            //
            // Safe as an ungrouped orWhere because a relation manager only ever
            // queries the one owner record.
            ->modifyQueryUsing(fn (Builder $query): Builder => $this->showsSiblings()
                ? $query->orWhere('parent_id', $theme->parent_id)
                : $query)
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('catalog_items_count')
                    ->label('Sets')
                    ->counts('catalogItems')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make()
                    ->label('Add subtheme')
                    ->hidden(fn (): bool => $this->showsSiblings()),
            ])
            ->recordActions([
                // Opens the subtheme as a record in its own right.
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Theme $record): string => ThemeResource::getUrl('view', ['record' => $record]))
                    // No point linking the page you are already on.
                    ->hidden(fn (Theme $record): bool => $record->is($theme)),
                EditAction::make()
                    ->hidden(fn (): bool => $this->showsSiblings()),
                DeleteAction::make()
                    ->hidden(fn (): bool => $this->showsSiblings()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->hidden(fn (): bool => $this->showsSiblings()),
            ]);
    }
}
