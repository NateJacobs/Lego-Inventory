<?php

namespace App\Filament\Resources\BulkBricks;

use App\Filament\Resources\BulkBricks\Pages\CreateBulkBrick;
use App\Filament\Resources\BulkBricks\Pages\EditBulkBrick;
use App\Filament\Resources\BulkBricks\Pages\ListBulkBricks;
use App\Filament\Resources\BulkBricks\Schemas\BulkBrickForm;
use App\Filament\Resources\BulkBricks\Tables\BulkBricksTable;
use App\Models\BulkBrick;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BulkBrickResource extends Resource
{
    protected static ?string $model = BulkBrick::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BulkBrickForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BulkBricksTable::configure($table);
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
            'index' => ListBulkBricks::route('/'),
            'create' => CreateBulkBrick::route('/create'),
            'edit' => EditBulkBrick::route('/{record}/edit'),
        ];
    }
}
