<?php

namespace App\Models;

use App\Observers\CatalogItemObserver;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $fillable = [
        'brickset_id',
        'set_number',
        'set_number_variant',
        'name',
        'piece_count',
        'minifig_count',
        'retail_price',
        'year',
        'theme',
        'sub_theme',
        'theme_group',
        'image_path',
        'thumbnail_path',
        'brickset_url',
    ];

    protected $appends = [
        'total_retail_price',
        'total_set_pieces',
    ];

    protected $attributes = [
        'current_value_new' => 0,
        'current_value_used' => 0,
        'retail_price' => 0,
    ];

    public function sets()
    {
        return $this->hasMany('App\Models\Set');
    }

    public function theme()
    {
        return $this->belongsTo('App\Models\Theme', 'theme_id', 'id');
    }

    public function subtheme()
    {
        return $this->belongsTo('App\Models\Theme', 'subtheme_id', 'id');
    }

    public static function boot()
    {
        parent::boot();
        static::observe(CatalogItemObserver::class);
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
