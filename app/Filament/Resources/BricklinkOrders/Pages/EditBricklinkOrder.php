<?php

namespace App\Filament\Resources\BricklinkOrders\Pages;

use App\Filament\Resources\BricklinkOrders\BricklinkOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBricklinkOrder extends EditRecord
{
    protected static string $resource = BricklinkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
