<?php

namespace App\Filament\Resources\Sets;

use App\Filament\Resources\Sets\Pages\CreateSet;
use App\Filament\Resources\Sets\Pages\EditSet;
use App\Filament\Resources\Sets\Pages\ListSets;
use App\Filament\Resources\Sets\Schemas\SetForm;
use App\Filament\Resources\Sets\Tables\SetsTable;
use App\Models\Set;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SetResource extends Resource
{
    protected static ?string $model = Set::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SetsTable::configure($table);
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
            'index' => ListSets::route('/'),
            'create' => CreateSet::route('/create'),
            'edit' => EditSet::route('/{record}/edit'),
        ];
    }
}
