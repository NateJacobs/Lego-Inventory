<?php

namespace App\Models;

use App\Observers\CatalogItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(CatalogItemObserver::class)]
class CatalogItem extends Model
{
    protected $fillable = [
        'brickset_id',
        'set_number',
        'set_number_variant',
        'type',
        'name',
        'piece_count',
        'minifig_count',
        'retail_price',
        'year',
        'theme_id',
        'theme_group',
        'image_path',
        'thumbnail_path',
        'brickset_url',
    ];

    protected $casts = [
        'prices_updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_retail_price',
        'total_set_pieces',
    ];

    protected $attributes = [
        'current_value_new' => 0,
        'current_value_used' => 0,
        'retail_price' => 0,
        // BrickLink builds its URL from the item type, so an absent one asks
        // for /items///price and comes back as a malformed URI.
        'type' => 'set',
    ];

    /**
     * How Brickset identifies this item, e.g. "71053-9".
     */
    public function getItemNumberAttribute(): string
    {
        return $this->set_number.'-'.(int) $this->set_number_variant;
    }

    /**
     * What to ask BrickLink for.
     *
     * Usually the Brickset number, which BrickLink shares. Collectable
     * minifigures are the exception: Brickset files Big Bad Wolf as 71053-9
     * while BrickLink calls the same polybag colshr-9, encoding the series in
     * a way nothing in the Brickset response gives us. So bricklink_id doubles
     * as an override — set it by hand and lookups follow it.
     */
    public function getBricklinkLookupNumberAttribute(): string
    {
        return filled($this->bricklink_id) ? $this->bricklink_id : $this->item_number;
    }

    /**
     * Whether both pictures are actually on the public disk.
     *
     * The columns holding a path is not enough: the files live outside the
     * repository and the database, so a rebuilt or migrated server can have
     * every path recorded and not one picture to show.
     */
    public function hasStoredImages(): bool
    {
        $disk = Storage::disk('public');

        foreach ([$this->image_path, $this->thumbnail_path] as $path) {
            if (blank($path) || ! $disk->exists($path)) {
                return false;
            }
        }

        return true;
    }

    public function sets()
    {
        return $this->hasMany('App\Models\Set');
    }

    /**
     * The most specific theme Brickset gives for this set — a subtheme when it
     * has one, otherwise a top-level theme.
     */
    public function theme()
    {
        return $this->belongsTo('App\Models\Theme', 'theme_id', 'id');
    }

    /**
     * The top-level theme, whether the set sits in it directly or under one of
     * its subthemes. Derived by walking up, so it cannot disagree with theme().
     */
    public function getRootThemeAttribute(): ?Theme
    {
        return $this->theme?->parent_id ? $this->theme->theme : $this->theme;
    }

    /**
     * The subtheme, when the set is filed under one rather than straight into a
     * top-level theme.
     */
    public function getSubthemeAttribute(): ?Theme
    {
        return $this->theme?->parent_id ? $this->theme : null;
    }

    public function getTotalRetailPriceAttribute()
    {
        if (isset($this->attributes['retail_price']) && isset($this->attributes['sets_count'])) {
            return $this->attributes['retail_price'] * $this->attributes['sets_count'];
        }

    }

    public function getTotalUsedValueAttribute()
    {
        if (isset($this->attributes['current_value_used']) && isset($this->attributes['sets_count'])) {
            return $this->attributes['current_value_used'] * $this->attributes['sets_count'];
        }
    }

    public function getTotalNewValueAttribute()
    {
        if (isset($this->attributes['current_value_new']) && isset($this->attributes['sets_count'])) {
            return $this->attributes['current_value_new'] * $this->attributes['sets_count'];
        }
    }

    public function getTotalSetPiecesAttribute()
    {
        if (isset($this->attributes['piece_count']) && isset($this->attributes['sets_count'])) {
            return $this->attributes['piece_count'] * $this->attributes['sets_count'];
        }
    }
}
