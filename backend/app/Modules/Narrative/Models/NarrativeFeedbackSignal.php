<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NarrativeFeedbackSignal: Represents a narrative-driven influence intended for a future simulation tick.
 */
class NarrativeFeedbackSignal extends Model
{
    protected $fillable = [
        'universe_id',
        'apply_at_tick',
        'type',
        'payload',
        'status'
    ];

    protected $casts = [
        'payload' => 'array',
        'apply_at_tick' => 'integer'
    ];

    /**
     * Scope for pending signals for a specific universe and tick.
     */
    public function scopePendingForTick($query, int $universeId, int $tick)
    {
        return $query->where('universe_id', $universeId)
                     ->where('apply_at_tick', $tick)
                     ->where('status', 'pending');
    }
}
