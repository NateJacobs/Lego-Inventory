<?php

namespace App\Filament\Resources\Sets\Pages;

use App\Filament\Resources\Sets\SetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSet extends EditRecord
{
    protected static string $resource = SetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
