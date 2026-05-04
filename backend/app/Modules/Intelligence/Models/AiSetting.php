<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
        'is_secret',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];
}
