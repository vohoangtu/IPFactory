<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchEvent extends Model
{
    protected $fillable = ['universe_id', 'from_tick', 'event_type', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
