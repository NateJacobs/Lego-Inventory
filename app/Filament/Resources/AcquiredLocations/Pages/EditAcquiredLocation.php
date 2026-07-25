<?php

namespace App\Filament\Resources\AcquiredLocations\Pages;

use App\Filament\Resources\AcquiredLocations\AcquiredLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcquiredLocation extends EditRecord
{
    protected static string $resource = AcquiredLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
