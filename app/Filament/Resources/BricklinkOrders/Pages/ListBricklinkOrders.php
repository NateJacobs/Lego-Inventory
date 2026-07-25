<?php

namespace App\Filament\Resources\BricklinkOrders\Pages;

use App\Filament\Resources\BricklinkOrders\BricklinkOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBricklinkOrders extends ListRecords
{
    protected static string $resource = BricklinkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
