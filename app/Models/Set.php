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

    /**
     * How far under MSRP this copy was bought, as a percentage, or null when
     * there is nothing to compare against.
     *
     * retail_price is a decimal column with no cast, so it arrives as the
     * string "0.00" for a set Brickset gives no price for — and "0.00" is
     * truthy in PHP, so a ! $retail guard let it through and divided by zero.
     * Compare numerically instead.
     */
    public function getSavingsPercentAttribute(): ?float
    {
        $retail = (float) ($this->catalogItem?->retail_price ?? 0);

        if ($retail <= 0 || $this->purchase_price === null) {
            return null;
        }

        return round((($retail - (float) $this->purchase_price) / $retail) * 100, 1);
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
