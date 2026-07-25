<?php

namespace App\Filament\Resources\Sets\Pages;

use App\Filament\Resources\Sets\SetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSets extends ListRecords
{
    protected static string $resource = SetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
