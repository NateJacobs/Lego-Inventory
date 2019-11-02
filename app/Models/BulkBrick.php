<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkBrick extends Model
{
    public $dates = [
        'acquired_date',
    ];

    public function acquiredLocation()
    {
        return $this->belongsTo('App\Models\AcquiredLocation');
    }
}
