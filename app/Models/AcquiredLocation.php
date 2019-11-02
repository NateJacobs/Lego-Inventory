<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcquiredLocation extends Model
{
    public function sets()
    {
        return $this->hasMany('App\Models\Set');
    }

    public function bulkBricks()
    {
        return $this->hasMany('App\Models\BulkBrick');
    }
}
