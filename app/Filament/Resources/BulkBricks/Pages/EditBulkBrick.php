<?php

namespace App\Filament\Resources\BulkBricks\Pages;

use App\Filament\Resources\BulkBricks\BulkBrickResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBulkBrick extends EditRecord
{
    protected static string $resource = BulkBrickResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
