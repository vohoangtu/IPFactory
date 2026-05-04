<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NarrativeState: Persists the high-level narrative context (Arcs, Conflicts) across ticks.
 */
class NarrativeState extends Model
{
    protected $fillable = [
        'universe_id',
        'current_arc',
        'active_conflicts',
        'dominant_ideologies',
        'last_tick'
    ];

    protected $casts = [
        'active_conflicts' => 'array',
        'dominant_ideologies' => 'array',
    ];
}
