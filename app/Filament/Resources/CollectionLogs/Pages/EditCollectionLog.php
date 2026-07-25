<?php

namespace App\Filament\Resources\CollectionLogs\Pages;

use App\Filament\Resources\CollectionLogs\CollectionLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollectionLog extends EditRecord
{
    protected static string $resource = CollectionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
