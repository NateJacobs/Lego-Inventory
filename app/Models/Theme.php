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

    public function catalogItems()
    {
        return $this->hasMany('App\Models\CatalogItem');
    }
}
