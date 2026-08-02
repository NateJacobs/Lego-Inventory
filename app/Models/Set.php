<?php

namespace App\Models;

use App\Observers\SetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(SetObserver::class)]
class Set extends Model
{
    // The table has always carried deleted_at, but without this trait a delete
    // was permanent — an accidental click destroyed an owned copy outright.
    use SoftDeletes;

    protected $fillable = [
        'catalog_item_id',
        'storage_location_id',
        'acquired_location_id',
        'purchase_price',
        'purchase_date',
        'current_condition',
        'notes',
    ];

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
