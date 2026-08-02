<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkBrick extends Model
{
    protected $fillable = [
        'acquired_location_id',
        'piece_count',
        'cost',
        'value',
        'acquired_date',
        'notes',
    ];

    protected $casts = [
        'acquired_date' => 'date',
    ];

    public function acquiredLocation()
    {
        return $this->belongsTo('App\Models\AcquiredLocation');
    }
}
