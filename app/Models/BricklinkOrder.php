<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BricklinkOrder extends Model
{
    protected $casts = [
        'purchase_date' => 'datetime',
    ];

    /**
     * total_cost is stored, not derived. Orders placed before 2005 only ever
     * recorded a total — their order_cost and shipping_cost are both zero — so
     * computing the total from those two would discard what was actually paid.
     */
    public $fillable = [
        'purchase_date',
        'seller_name',
        'store_name',
        'order_number',
        'pieces',
        'order_cost',
        'shipping_cost',
        'total_cost',
        'details',
        'notes',
    ];
}
