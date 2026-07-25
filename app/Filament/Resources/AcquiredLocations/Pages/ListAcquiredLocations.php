<?php

namespace App\Filament\Resources\AcquiredLocations\Pages;

use App\Filament\Resources\AcquiredLocations\AcquiredLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcquiredLocations extends ListRecords
{
    protected static string $resource = AcquiredLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
