<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The application code (CatalogItemObserver, the Theme/CatalogItem models,
     * and their Nova resources) relates catalog items to themes through the
     * integer foreign keys `theme_id` and `subtheme_id`. The original
     * create_set_info migration only ever stored the theme as free-text
     * (`theme` / `sub_theme`), so those relations reference columns that never
     * existed. This migration adds the missing columns and backfills them from
     * the legacy string values.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (! Schema::hasColumn('catalog_items', 'theme_id')) {
                $table->unsignedBigInteger('theme_id')->nullable()->index();
            }

            // Not a foreign key: CatalogItemObserver::getSubTheme() stores 0
            // (not null) when a set has no subtheme, which a constraint would reject.
            if (! Schema::hasColumn('catalog_items', 'subtheme_id')) {
                $table->unsignedBigInteger('subtheme_id')->nullable()->index();
            }
        });

        $this->backfillThemeIds();
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            foreach (['theme_id', 'subtheme_id'] as $column) {
                if (Schema::hasColumn('catalog_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Map the legacy `theme` / `sub_theme` strings onto theme ids using the
     * same lookup the observer performs (top-level theme by name, then a
     * subtheme whose parent is that theme; 0 when no subtheme matches).
     */
    protected function backfillThemeIds(): void
    {
        if (! Schema::hasColumn('catalog_items', 'theme')) {
            return;
        }

        DB::table('catalog_items')
            ->whereNull('theme_id')
            ->where('theme', '!=', '')
            ->orderBy('id')
            ->each(function ($item) {
                $themeId = DB::table('themes')
                    ->whereNull('parent_id')
                    ->where('name', $item->theme)
                    ->value('id');

                if (! $themeId) {
                    return;
                }

                $subthemeId = 0;

                if (! empty($item->sub_theme)) {
                    $subthemeId = DB::table('themes')
                        ->where('parent_id', $themeId)
                        ->where('name', $item->sub_theme)
                        ->value('id') ?? 0;
                }

                DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update([
                        'theme_id' => $themeId,
                        'subtheme_id' => $subthemeId,
                    ]);
            });
    }
};
