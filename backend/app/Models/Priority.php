<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $fillable = [
        'value',
        'label',
        'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'is_active' => 'boolean',
    ];
}
