<?php

namespace App\Filament\Resources\CatalogItems\RelationManagers;

use App\Models\Set;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

/**
 * The individual copies of a catalog item that are actually owned. This mirrors
 * the Sets resource, minus its catalog item picker — the owner record supplies
 * that here.
 */
class SetsRelationManager extends RelationManager
{
    protected static string $relationship = 'sets';

    protected static ?string $title = 'Owned copies';

    /**
     * Filament makes relation managers read-only on view pages by default.
     * Adding a copy is the main thing you want to do while looking at a set,
     * so keep the actions available there too.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('current_condition')
                    ->label('Condition')
                    ->options([
                        'parted out' => 'Parted Out',
                        'assembled' => 'Assembled',
                        'misb' => 'MISB',
                    ])
                    ->required(),
                TextInput::make('purchase_price')
                    ->numeric()
                    ->prefix('$'),
                Select::make('storage_location_id')
                    ->label('Storage location')
                    ->relationship('storageLocation', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('acquired_location_id')
                    ->label('Acquired from')
                    ->relationship('acquiredLocation', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('purchase_date'),
                MarkdownEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('current_condition')
            ->columns([
                TextColumn::make('current_condition')
                    ->label('Condition')
                    ->badge()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Paid')
                    ->money('usd')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('savings')
                    ->label('Savings')
                    ->state(function (Set $record): ?string {
                        $retail = $record->catalogItem?->retail_price;

                        if (! $retail || $record->purchase_price === null) {
                            return null;
                        }

                        return round((($retail - $record->purchase_price) / $retail) * 100, 1).'%';
                    })
                    ->placeholder('—'),
                TextColumn::make('purchase_date')
                    ->label('Purchased')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('storageLocation.name')
                    ->label('Storage')
                    ->sortable()
                    ->placeholder('Unassigned'),
                TextColumn::make('acquiredLocation.name')
                    ->label('Acquired from')
                    ->sortable()
                    ->placeholder('Unknown'),
                TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add copy'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }
}
