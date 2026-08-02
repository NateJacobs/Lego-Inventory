<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    public $fillable = [
        'name',
        'parent_id',
    ];

    public function theme()
    {
        return $this->belongsTo('App\Models\Theme', 'parent_id');
    }

    public function subthemes()
    {
        return $this->hasMany('App\Models\Theme', 'parent_id');
    }

    /**
     * Catalog items whose primary theme is this one. Only ever populated for
     * top-level themes — a set nested under a subtheme records the parent here
     * and the subtheme in subtheme_id.
     */
    public function catalogItems()
    {
        return $this->hasMany('App\Models\CatalogItem');
    }

    /**
     * Catalog items filed under this theme as their subtheme. The counterpart
     * to catalogItems(), and the only way to reach a subtheme's sets.
     */
    public function subthemeCatalogItems()
    {
        return $this->hasMany('App\Models\CatalogItem', 'subtheme_id');
    }
}
