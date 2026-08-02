<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageLocation extends Model
{
    protected $fillable = [
        'name',
        'city',
        'state',
        'zip_code',
    ];

    public function sets()
    {
        return $this->hasMany('App\Models\Set');
    }
}
