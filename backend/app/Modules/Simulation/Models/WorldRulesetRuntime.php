<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldRulesetRuntime extends Model
{
    protected $table = 'world_ruleset_runtime';
    
    protected $fillable = [
        'world_id',
        'ruleset_id',
        'active_tick',
        'ambient_energy',
        'reality_stability',
        'dynamic_axioms'
    ];

    protected $casts = [
        'active_tick' => 'integer',
        'ambient_energy' => 'array',
        'reality_stability' => 'float',
        'dynamic_axioms' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function ruleset(): BelongsTo
    {
        return $this->belongsTo(RuleSetDefinition::class, 'ruleset_id');
    }
}
