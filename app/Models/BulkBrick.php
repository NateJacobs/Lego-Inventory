<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkBrick extends Model
{
    protected $casts = [
        'acquired_date' => 'date',
    ];

    public function acquiredLocation()
    {
        return $this->belongsTo('App\Models\AcquiredLocation');
    }
}
