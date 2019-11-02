<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BricklinkOrder extends Model
{
    public $dates = [
        'purchase_date',
    ];

    public $fillable = [
        'purchase_date',
        'seller_name',
        'store_name',
        'order_number',
        'pieces',
        'order_cost',
        'shipping_cost',
        'total_cost',
    ];

    public static function boot()
    {
        parent::boot();

        // calculate total cost of order before DB insert
        static::creating(function ($item)  {
			$item->total_cost = $item->order_cost + $item->shipping_cost;

            return $item;
        });
    }
}
