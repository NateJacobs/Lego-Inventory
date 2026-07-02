<?php

namespace App\Models;

use App\Observers\SetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(SetObserver::class)]
class Set extends Model
{
    protected $casts = [
        'purchase_date' => 'datetime',
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
}
