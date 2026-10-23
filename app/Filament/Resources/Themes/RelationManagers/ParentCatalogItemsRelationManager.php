<?php

namespace App\Filament\Resources\Themes\RelationManagers;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything in the theme a subtheme belongs to, shown on the subtheme's page
 * beside its own sets — the wider view its parent's page gives, without
 * going up to it.
 *
 * Only on a subtheme: a top-level theme has no parent, and its own table
 * already spans its subthemes.
 */
class ParentCatalogItemsRelationManager extends CatalogItemsRelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->parent_id !== null
            && parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Sets in '.($ownerRecord->theme?->name ?? 'the parent theme');
    }

    protected function themeToShow(): Theme
    {
        return $this->getOwnerRecord()->theme;
    }

    /**
     * The whole point here is seeing which subtheme each set sits in, including
     * the ones that are not the subtheme being viewed.
     */
    protected function showsSubthemeColumn(): bool
    {
        return true;
    }
}
