<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $fillable = [
        'feature',
        'driver',
        'model',
        'input',
        'output',
        'latency_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];
}
