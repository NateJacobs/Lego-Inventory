<?php

namespace App\Filament\Resources\CatalogItems\Pages;

use App\Exceptions\BricklinkPriceException;
use App\Exceptions\BricksetImageException;
use App\Exceptions\BricksetLookupException;
use App\Filament\Resources\CatalogItems\CatalogItemResource;
use App\Models\CatalogItem;
use App\UpdateCatalogItem;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCatalogItem extends ViewRecord
{
    protected static string $resource = CatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The single-record counterpart to the bulk action on the list table.
            Action::make('updateBricklinkPrices')
                ->label('Update BrickLink prices')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    try {
                        (new UpdateCatalogItem($this->getRecord()))->updateBricklink();
                    } catch (BricklinkPriceException $e) {
                        Notification::make()
                            ->title('Could not fetch BrickLink prices')
                            ->body($e->getMessage())
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->refreshFormData(['current_value_new', 'current_value_used', 'prices_updated_at']);

                    Notification::make()
                        ->title('BrickLink prices updated')
                        ->success()
                        ->send();
                }),
            $this->refreshBricksetDetailsAction(),
            $this->downloadImagesAction(),
            $this->overrideBricklinkIdAction(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Everything describing a set comes from Brickset at the moment it was
     * catalogued and was never read again. This asks for it afresh, so a set
     * renamed, re-themed, or given a piece count it was missing on Brickset is
     * corrected here.
     *
     * Confirmed first, since Brickset wins: a detail that has since changed
     * there replaces what is held now.
     */
    protected function refreshBricksetDetailsAction(): Action
    {
        return Action::make('refreshBricksetDetails')
            ->label('Refresh Brickset details')
            ->icon('heroicon-o-arrow-down-tray')
            ->requiresConfirmation()
            ->modalHeading('Refresh this set from Brickset')
            ->modalDescription('Name, year, piece and minifigure counts, MSRP and theme will be replaced with what Brickset holds now. Pictures, BrickLink values and the item type are left alone.')
            ->modalSubmitActionLabel('Refresh')
            ->action(function (CatalogItem $record): void {
                $before = $record->only([
                    'name', 'year', 'piece_count', 'minifig_count', 'retail_price', 'theme_id',
                ]);

                try {
                    (new UpdateCatalogItem($record))->updateDetails();
                } catch (BricksetLookupException $e) {
                    Notification::make()
                        ->title('Could not reach Brickset')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();

                    return;
                }

                $this->refreshFormData(array_keys($before));

                $changed = collect($before)
                    ->reject(fn ($value, $field) => $value == $record->{$field})
                    ->keys();

                Notification::make()
                    ->title($changed->isEmpty()
                        ? 'Already up to date with Brickset'
                        : 'Refreshed from Brickset')
                    ->body($changed->isEmpty() ? null : 'Changed: '.$changed->implode(', ').'.')
                    ->success()
                    ->send();
            });
    }

    /**
     * The pictures live on the public disk rather than in the database, so a
     * rebuilt or migrated server has every path recorded and no files to serve.
     * This fetches them again from Brickset, one set at a time; use
     * collection:download-images for the whole catalogue.
     */
    protected function downloadImagesAction(): Action
    {
        return Action::make('downloadImages')
            ->label('Download images')
            ->icon('heroicon-o-photo')
            ->action(function (CatalogItem $record): void {
                try {
                    // Forced: asking for it by hand means fetch it, even if
                    // there is a file there already.
                    (new UpdateCatalogItem($record))->updateImages(force: true);
                } catch (BricksetImageException|BricksetLookupException $e) {
                    Notification::make()
                        ->title('Could not download the images')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();

                    return;
                }

                $this->refreshFormData(['image_path', 'thumbnail_path']);

                Notification::make()
                    ->title('Images downloaded from Brickset')
                    ->success()
                    ->send();
            });
    }

    /**
     * BrickLink numbers collectable minifigures differently to Brickset — Big
     * Bad Wolf is 71053-9 there and colshr-9 on BrickLink — and nothing in the
     * Brickset response maps between them, so the number has to be given by
     * hand. The rest of the Details form stays read-only, since the observer
     * owns those fields; this is the one value a person has to supply.
     */
    protected function overrideBricklinkIdAction(): Action
    {
        return Action::make('overrideBricklinkId')
            ->label('Override BrickLink ID')
            ->icon('heroicon-o-link')
            ->modalHeading('Override the BrickLink ID')
            ->modalDescription('Leave this empty to use the Brickset set number.')
            ->modalSubmitActionLabel('Save and fetch prices')
            ->fillForm(fn (CatalogItem $record): array => [
                'bricklink_id' => $record->bricklink_id,
            ])
            ->schema(fn (CatalogItem $record): array => [
                TextInput::make('bricklink_id')
                    ->label('BrickLink item number')
                    ->placeholder($record->item_number)
                    ->helperText('For a collectable minifigure this looks like colshr-9.')
                    ->maxLength(100),
            ])
            ->action(function (CatalogItem $record, array $data): void {
                // Not mass assigned: the column is deliberately outside
                // $fillable so only this action and the observer write it.
                $record->bricklink_id = filled($data['bricklink_id'])
                    ? trim($data['bricklink_id'])
                    : null;
                $record->save();

                // Price it straight away, so a wrong number is caught here
                // rather than next time the refresh runs.
                try {
                    (new UpdateCatalogItem($record))->updateBricklink();
                } catch (BricklinkPriceException $e) {
                    Notification::make()
                        ->title('Saved, but BrickLink returned nothing')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();

                    return;
                }

                $this->refreshFormData(['bricklink_id', 'current_value_new', 'current_value_used', 'prices_updated_at']);

                Notification::make()
                    ->title('Priced as '.$record->fresh()->bricklink_lookup_number)
                    ->success()
                    ->send();
            });
    }
}
