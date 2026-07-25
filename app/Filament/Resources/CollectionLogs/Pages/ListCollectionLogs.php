<?php

namespace App\Filament\Resources\CollectionLogs\Pages;

use App\Filament\Resources\CollectionLogs\CollectionLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectionLogs extends ListRecords
{
    protected static string $resource = CollectionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
