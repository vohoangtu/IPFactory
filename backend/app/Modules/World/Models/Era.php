<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Era extends Model
{
    protected $fillable = [
        'universe_id',
        'start_tick',
        'end_tick',
        'title',
        'summary',
        'detected_at_tick',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
