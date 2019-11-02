<?php

namespace App\Models;

use App\Observers\SetObserver;
use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    public $dates = [
        'purchase_date',
    ];

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem');
    }

    public function storageLocation()
    {
        return $this->belongsTo('App\Models\StorageLocation');
    }

    public function acquiredLocation()
    {
        return $this->belongsTo('App\Models\AcquiredLocation');
    }

    public static function boot()
    {
        parent::boot();
        static::observe(SetObserver::class);
    }
}
