<?php

namespace App\Filament\Resources\AcquiredLocations;

use App\Filament\Resources\AcquiredLocations\Pages\CreateAcquiredLocation;
use App\Filament\Resources\AcquiredLocations\Pages\EditAcquiredLocation;
use App\Filament\Resources\AcquiredLocations\Pages\ListAcquiredLocations;
use App\Filament\Resources\AcquiredLocations\Schemas\AcquiredLocationForm;
use App\Filament\Resources\AcquiredLocations\Tables\AcquiredLocationsTable;
use App\Models\AcquiredLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AcquiredLocationResource extends Resource
{
    protected static ?string $model = AcquiredLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AcquiredLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcquiredLocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcquiredLocations::route('/'),
            'create' => CreateAcquiredLocation::route('/create'),
            'edit' => EditAcquiredLocation::route('/{record}/edit'),
        ];
    }
}
