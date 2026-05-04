<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AgentDecision extends Model
{
    protected $fillable = [
        'actor_id',
        'universe_id',
        'tick',
        'action_type',
        'target_id',
        'utility_score',
        'impact',
        'reasoning',
        'confidence',
        'traits_snapshot',
        'context_snapshot',
    ];

    protected $casts = [
        'traits_snapshot' => 'array',
        'context_snapshot' => 'array',
        'impact' => 'array',
        'utility_score' => 'float',
        'confidence' => 'float',
    ];

    public function actor()
    {
        return $this->belongsTo(Actor::class);
    }

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }
}
