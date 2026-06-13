<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Simulation\Models\WorldRulesetRuntime;

class World extends Model
{
    use HasFactory;
    protected $fillable = [
        'multiverse_id', 'name', 'slug', 'axiom', 'world_seed', 'origin', 
        'primary_ruleset_id', 'current_genre', 'base_genre', 'active_genre_weights', 
        'is_autonomic', 'global_tick', 'is_chaotic', 'snapshot_interval',
        'civilization_era', 'power_system_type', 'power_system_bootstrap_energy', 'version'
    ];

    protected $casts = [
        'axiom' => 'array',
        'world_seed' => 'array',
        'active_genre_weights' => 'array',
        'is_autonomic' => 'boolean',
        'global_tick' => 'integer',
        'is_chaotic' => 'boolean',
        'snapshot_interval' => 'integer',
        'power_system_bootstrap_energy' => 'float',
        'version' => 'integer',
    ];

    public function multiverse(): BelongsTo
    {
        return $this->belongsTo(Multiverse::class);
    }

    public function universes(): HasMany
    {
        return $this->hasMany(Universe::class);
    }

    public function epochs(): HasMany
    {
        return $this->hasMany(Epoch::class);
    }

    public function primaryRuleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSetDefinition::class, 'primary_ruleset_id');
    }

    public function rulesetRuntime(): HasOne
    {
        return $this->hasOne(WorldRulesetRuntime::class);
    }

    /**
     * Boot the model and handle lifecycle events.
     */
    protected static function booted(): void
    {
        static::created(function (World $world) {
            // Automatically initialize the ruleset runtime if it doesn't exist
            if (!$world->rulesetRuntime()->exists()) {
                $world->rulesetRuntime()->create([
                    'ruleset_id' => $world->primary_ruleset_id ?? 'realistic_modern',
                    'reality_stability' => 1.0,
                    'active_tick' => 0,
                    'ambient_energy' => [],
                    'dynamic_axioms' => []
                ]);
            }
        });
    }
}
