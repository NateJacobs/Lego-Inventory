<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionLog extends Model
{
    protected $casts = [
        'date' => 'date',
    ];

    public $fillable = [
        'date',
        'used_value',
        'retail_value',
        'new_value',
        'total_sets',
        'piece_count',
        'notes',
    ];
}
