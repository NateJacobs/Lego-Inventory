<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * catalog_items recorded its place in the theme tree twice: theme_id always
     * held the top-level theme, subtheme_id the leaf. themes.parent_id already
     * encodes that tree, so theme_id was derivable — and the pair had drifted
     * apart on the rows whose subtheme name ("General", "Miscellaneous",
     * "Promotional") is reused under several parents.
     *
     * Point theme_id at the most specific theme instead — the subtheme when a
     * set has one, the top-level theme when it does not — and drop subtheme_id.
     */
    public function up(): void
    {
        if (Schema::hasColumn('catalog_items', 'subtheme_id')) {
            $this->pointAtMostSpecificTheme();

            Schema::table('catalog_items', function (Blueprint $table) {
                $table->dropColumn('subtheme_id');
            });
        }

        // theme_id is now the only route from a set to its theme, so every
        // theme lookup goes through it.
        if (! Schema::hasIndex('catalog_items', 'catalog_items_theme_id_index')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->index('theme_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('catalog_items', 'catalog_items_theme_id_index')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                $table->dropIndex('catalog_items_theme_id_index');
            });
        }

        if (Schema::hasColumn('catalog_items', 'subtheme_id')) {
            return;
        }

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->integer('subtheme_id')->nullable()->after('theme_id');
        });

        $this->splitThemeFromSubtheme();
    }

    /**
     * Move each set from its top-level theme down onto its subtheme.
     */
    protected function pointAtMostSpecificTheme(): void
    {
        $themes = $this->themes();

        DB::table('catalog_items')
            ->select('id', 'theme_id', 'subtheme_id')
            ->where('subtheme_id', '>', 0)
            ->orderBy('id')
            ->each(function ($item) use ($themes) {
                $subtheme = $themes->get($item->subtheme_id);

                // A subtheme_id pointing at nothing tells us no more than
                // theme_id already does, so leave the row alone.
                if (! $subtheme) {
                    return;
                }

                DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update(['theme_id' => $this->mostSpecificThemeId($item, $subtheme, $themes)]);
            });
    }

    /**
     * Usually the subtheme itself. When the subtheme belongs to a different
     * parent than theme_id claims, the two disagree about where the set sits:
     * theme_id wins, because a subtheme name like "General" says nothing on its
     * own while Brickset's theme is unambiguous. The set is filed under that
     * theme's own subtheme of the same name, created if it does not exist yet.
     */
    protected function mostSpecificThemeId(object $item, object $subtheme, Collection $themes): int
    {
        if (empty($item->theme_id) || (int) $subtheme->parent_id === (int) $item->theme_id) {
            return (int) $subtheme->id;
        }

        $existing = $themes->first(
            fn ($theme) => $theme->name === $subtheme->name
                && (int) $theme->parent_id === (int) $item->theme_id
        );

        if ($existing) {
            return (int) $existing->id;
        }

        $id = DB::table('themes')->insertGetId([
            'name' => $subtheme->name,
            'parent_id' => $item->theme_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $themes->put($id, (object) [
            'id' => $id,
            'name' => $subtheme->name,
            'parent_id' => $item->theme_id,
        ]);

        return $id;
    }

    /**
     * Put each set back on its top-level theme, with the leaf in subtheme_id.
     * Sets that never had a subtheme come back as 0 rather than null — the
     * sentinel the old observer wrote — since the two were interchangeable.
     */
    protected function splitThemeFromSubtheme(): void
    {
        $themes = $this->themes();

        DB::table('catalog_items')
            ->select('id', 'theme_id')
            ->orderBy('id')
            ->each(function ($item) use ($themes) {
                $theme = $themes->get($item->theme_id);

                DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update($theme && $theme->parent_id
                        ? ['theme_id' => $theme->parent_id, 'subtheme_id' => $theme->id]
                        : ['subtheme_id' => 0]);
            });
    }

    /**
     * The whole theme tree, keyed by id — a thousand-odd rows, so cheaper to
     * hold onto than to re-query per set.
     */
    protected function themes(): Collection
    {
        return DB::table('themes')->select('id', 'name', 'parent_id')->get()->keyBy('id');
    }
};
