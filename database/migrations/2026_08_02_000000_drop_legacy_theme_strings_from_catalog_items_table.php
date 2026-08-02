<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `catalog_items.theme` shadowed the CatalogItem::theme() relationship: an
     * attribute and a relation cannot share a name, so Eloquent returned the
     * legacy string instead of the related Theme, and Filament refused to
     * resolve the relationship at all (Select::getRelationship() bails when
     * $record->hasAttribute() matches the relation name).
     *
     * The theme_id / subtheme_id foreign keys added in 2026_07_01_000000 are
     * the authoritative source now, and CatalogItemObserver stopped writing the
     * strings, so drop them. Anything the earlier backfill skipped is resolved
     * first, this time creating themes that don't exist yet rather than leaving
     * the row unmapped.
     */
    public function up(): void
    {
        $this->backfillMissingThemeIds();

        Schema::table('catalog_items', function (Blueprint $table) {
            foreach (['theme', 'sub_theme'] as $column) {
                if (Schema::hasColumn('catalog_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Restore the columns and rebuild their values from the theme relations, so
     * the migration round-trips without losing the legacy strings.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (! Schema::hasColumn('catalog_items', 'theme')) {
                $table->string('theme', 100)->nullable();
            }

            if (! Schema::hasColumn('catalog_items', 'sub_theme')) {
                $table->string('sub_theme', 100)->nullable();
            }
        });

        DB::table('catalog_items')
            ->select('catalog_items.id', 'catalog_items.theme_id', 'catalog_items.subtheme_id')
            ->orderBy('catalog_items.id')
            ->each(function ($item) {
                DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update([
                        'theme' => DB::table('themes')->where('id', $item->theme_id)->value('name'),
                        'sub_theme' => DB::table('themes')->where('id', $item->subtheme_id)->value('name'),
                    ]);
            });
    }

    /**
     * 2026_07_01_000000 only mapped strings onto themes that already existed,
     * leaving theme_id null whenever the name was missing from `themes`. Repeat
     * the pass with the observer's create-on-miss behaviour.
     */
    protected function backfillMissingThemeIds(): void
    {
        if (! Schema::hasColumn('catalog_items', 'theme')) {
            return;
        }

        DB::table('catalog_items')
            ->whereNull('theme_id')
            ->whereNotNull('theme')
            ->where('theme', '!=', '')
            ->orderBy('id')
            ->each(function ($item) {
                $themeId = $this->themeId($item->theme, null);

                DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update([
                        'theme_id' => $themeId,
                        // 0 signals "no subtheme", matching CatalogItemObserver.
                        'subtheme_id' => empty($item->sub_theme)
                            ? 0
                            : $this->themeId($item->sub_theme, $themeId),
                    ]);
            });
    }

    /**
     * The DB-facade equivalent of the observer's
     * Theme::firstOrCreate(['name' => ..., 'parent_id' => ...])->id.
     */
    protected function themeId(string $name, ?int $parentId): int
    {
        $query = DB::table('themes')->where('name', $name);

        $parentId === null
            ? $query->whereNull('parent_id')
            : $query->where('parent_id', $parentId);

        return $query->value('id') ?? DB::table('themes')->insertGetId([
            'name' => $name,
            'parent_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
