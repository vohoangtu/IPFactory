<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;

class Demiurge extends Model
{
    protected $fillable = [
        'name',
        'intention_type',
        'will_power',
        'essence_pool',
        'is_active',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'essence_pool' => 'float',
    ];
}
