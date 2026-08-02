<?php

namespace App\Filament\Resources\CatalogItems\Pages;

use App\Exceptions\BricklinkPriceException;
use App\Filament\Resources\CatalogItems\CatalogItemResource;
use App\UpdateCatalogItem;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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

                    $this->refreshFormData(['current_value_new', 'current_value_used']);

                    Notification::make()
                        ->title('BrickLink prices updated')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
