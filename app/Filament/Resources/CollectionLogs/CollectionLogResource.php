<?php

namespace App\Filament\Resources\CollectionLogs;

use App\Filament\Resources\CollectionLogs\Pages\CreateCollectionLog;
use App\Filament\Resources\CollectionLogs\Pages\EditCollectionLog;
use App\Filament\Resources\CollectionLogs\Pages\ListCollectionLogs;
use App\Filament\Resources\CollectionLogs\Schemas\CollectionLogForm;
use App\Filament\Resources\CollectionLogs\Tables\CollectionLogsTable;
use App\Models\CollectionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollectionLogResource extends Resource
{
    protected static ?string $model = CollectionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CollectionLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionLogsTable::configure($table);
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
            'index' => ListCollectionLogs::route('/'),
            'create' => CreateCollectionLog::route('/create'),
            'edit' => EditCollectionLog::route('/{record}/edit'),
        ];
    }
}
