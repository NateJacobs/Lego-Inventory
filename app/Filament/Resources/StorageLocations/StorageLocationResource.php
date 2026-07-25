<?php

namespace App\Filament\Resources\StorageLocations;

use App\Filament\Resources\StorageLocations\Pages\CreateStorageLocation;
use App\Filament\Resources\StorageLocations\Pages\EditStorageLocation;
use App\Filament\Resources\StorageLocations\Pages\ListStorageLocations;
use App\Filament\Resources\StorageLocations\Schemas\StorageLocationForm;
use App\Filament\Resources\StorageLocations\Tables\StorageLocationsTable;
use App\Models\StorageLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StorageLocationResource extends Resource
{
    protected static ?string $model = StorageLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StorageLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StorageLocationsTable::configure($table);
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
            'index' => ListStorageLocations::route('/'),
            'create' => CreateStorageLocation::route('/create'),
            'edit' => EditStorageLocation::route('/{record}/edit'),
        ];
    }
}
