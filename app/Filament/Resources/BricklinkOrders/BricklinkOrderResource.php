<?php

namespace App\Filament\Resources\BricklinkOrders;

use App\Filament\Resources\BricklinkOrders\Pages\CreateBricklinkOrder;
use App\Filament\Resources\BricklinkOrders\Pages\EditBricklinkOrder;
use App\Filament\Resources\BricklinkOrders\Pages\ListBricklinkOrders;
use App\Filament\Resources\BricklinkOrders\Schemas\BricklinkOrderForm;
use App\Filament\Resources\BricklinkOrders\Tables\BricklinkOrdersTable;
use App\Models\BricklinkOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BricklinkOrderResource extends Resource
{
    protected static ?string $model = BricklinkOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BricklinkOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BricklinkOrdersTable::configure($table);
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
            'index' => ListBricklinkOrders::route('/'),
            'create' => CreateBricklinkOrder::route('/create'),
            'edit' => EditBricklinkOrder::route('/{record}/edit'),
        ];
    }
}
