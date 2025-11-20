<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $fillable = ['capacity', 'remaining'];
    protected $casts = [
        'capacity' => 'int',
        'remaining' => 'int'
    ];
}
