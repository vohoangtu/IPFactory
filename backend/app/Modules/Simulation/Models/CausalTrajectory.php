<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CausalTrajectory extends Model
{
    protected $fillable = [
        'universe_id',
        'created_tick',
        'prediction_tick',
        'text',
        'confidence',
        'fulfilled',
        'source_snapshot_metrics',
    ];

    protected $casts = [
        'confidence' => 'float',
        'fulfilled' => 'boolean',
        'source_snapshot_metrics' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function actors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_causal_trajectory_beliefs')
            ->withPivot('belief_strength')
            ->withTimestamps();
    }
}
