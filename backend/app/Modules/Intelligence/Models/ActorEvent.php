<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Actor life timeline: one row per event (tick, event_type, context).
 * Feeds Chronicle / "life timeline" UI and culture/history trace.
 */
class ActorEvent extends Model
{
    protected $fillable = [
        'actor_id',
        'tick',
        'event_type',
        'context',
    ];

    protected $casts = [
        'tick' => 'integer',
        'context' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
