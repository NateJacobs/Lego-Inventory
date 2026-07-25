<?php

namespace App\Filament\Resources\BulkBricks\Pages;

use App\Filament\Resources\BulkBricks\BulkBrickResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBulkBricks extends ListRecords
{
    protected static string $resource = BulkBrickResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
