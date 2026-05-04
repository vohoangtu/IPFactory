<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Epoch extends Model
{
    protected $fillable = [
        'world_id', 'name', 'theme', 'description', 
        'start_tick', 'end_tick', 'axiom_modifiers', 'status'
    ];

    protected $casts = [
        'axiom_modifiers' => 'array',
        'start_tick' => 'integer',
        'end_tick' => 'integer',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
