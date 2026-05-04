<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;

class HistorianProfile extends Model
{
    protected $fillable = [
        'name',
        'personality',
        'bias',
        'writing_style',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
