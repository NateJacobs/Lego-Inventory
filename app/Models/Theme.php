<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    public $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * The theme this one sits under, or null when it is top-level. Themes are
     * only ever two deep.
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'parent_id');
    }

    public function subthemes(): HasMany
    {
        return $this->hasMany(Theme::class, 'parent_id');
    }

    /**
     * Sets filed under this exact theme. A set records its most specific theme,
     * so a top-level theme holds only those sets Brickset gives no subtheme
     * for; the rest hang off its subthemes.
     */
    public function catalogItems(): HasMany
    {
        return $this->hasMany(CatalogItem::class);
    }

    /**
     * Where this theme sits in the tree, e.g. "Harry Potter › General". Names
     * like "General" are reused under many parents and mean little alone.
     */
    public function getFullNameAttribute(): string
    {
        return $this->parent_id && $this->theme
            ? $this->theme->name.' › '.$this->name
            : $this->name;
    }

    /**
     * Sets in this theme plus, for a top-level theme, those in its subthemes.
     */
    public function totalCatalogItemsCount(): int
    {
        return CatalogItem::whereIn('theme_id', $this->selfAndSubthemeIds())->count();
    }

    /**
     * @return array<int, int>
     */
    public function selfAndSubthemeIds(): array
    {
        return [$this->id, ...$this->subthemes()->pluck('id')->all()];
    }

    /**
     * The counts a listing needs, as correlated subqueries rather than a query
     * per row. total_catalog_items_count spans this theme and its subthemes,
     * so a top-level theme never reports a misleading zero.
     */
    public function scopeWithSetCounts(Builder $query): Builder
    {
        return $query
            ->withCount(['catalogItems', 'subthemes'])
            ->selectSub(
                CatalogItem::query()
                    ->selectRaw('count(*)')
                    ->where(fn ($sets) => $sets
                        ->whereColumn('catalog_items.theme_id', 'themes.id')
                        ->orWhereIn('catalog_items.theme_id', fn ($subthemes) => $subthemes
                            ->from('themes as subthemes')
                            ->select('subthemes.id')
                            ->whereColumn('subthemes.parent_id', 'themes.id'))),
                'total_catalog_items_count'
            );
    }
}
