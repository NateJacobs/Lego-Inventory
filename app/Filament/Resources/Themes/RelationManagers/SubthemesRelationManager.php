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
use Illuminate\Database\Eloquent\Model;

class SubthemesRelationManager extends RelationManager
{
    protected static string $relationship = 'subthemes';

    protected static ?string $title = 'Subthemes';

    /**
     * Themes are only ever two deep, so a subtheme has nothing to show here.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->parent_id === null;
    }

    public function isReadOnly(): bool
    {
        return false;
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
        return $table
            ->recordTitleAttribute('name')
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
                    ->label('Add subtheme'),
            ])
            ->recordActions([
                // Opens the subtheme as a record in its own right.
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Theme $record): string => ThemeResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
