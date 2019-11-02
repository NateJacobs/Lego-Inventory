<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageLocation extends Model
{
    public function sets()
    {
        return $this->hasMany('App\Models\Set');
    }
}
