<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BricklinkOrder extends Model
{
    protected $casts = [
        'purchase_date' => 'datetime',
    ];

    protected $appends = [
        'total_cost',
    ];

    public $fillable = [
        'purchase_date',
        'seller_name',
        'store_name',
        'order_number',
        'pieces',
        'order_cost',
        'shipping_cost',
    ];

    /**
     * The order's total cost, always derived from item cost plus shipping.
     *
     * Computed on read so it stays correct when an order is edited, and so it
     * works without a dedicated (and easily-stale) total_cost column.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->order_cost + (float) $this->shipping_cost;
    }
}
