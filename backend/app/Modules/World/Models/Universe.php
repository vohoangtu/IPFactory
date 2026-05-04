<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Universe extends Model
{
    use HasFactory;
    protected $fillable = [
        'world_id', 'multiverse_id', 'saga_id', 'parent_universe_id', 'forked_at_tick',
        'current_tick', 'level', 'epoch', 'status', 'state_vector', 'name',
        'observation_load', 'last_observed_at', 'observer_bonus',
        'structural_coherence', 'entropy', 'fitness_score',
    ];

    protected $casts = [
        'state_vector' => 'array',
        'engine_manifest' => 'array',
        'observation_load' => 'float',
        'last_observed_at' => 'datetime',
        'observer_bonus' => 'float',
        'structural_coherence' => 'float',
        'entropy' => 'float',
        'kernel_genome' => 'array',
        'fitness_score' => 'float',
        'axioms' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function multiverse(): BelongsTo
    {
        return $this->belongsTo(Multiverse::class);
    }

    public function parentUniverse(): BelongsTo
    {
        return $this->belongsTo(Universe::class, 'parent_universe_id');
    }

    public function childUniverses(): HasMany
    {
        return $this->hasMany(Universe::class, 'parent_universe_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(UniverseSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(UniverseSnapshot::class)->latestOfMany('tick');
    }

    public function eras(): HasMany
    {
        return $this->hasMany(Era::class);
    }

    public function branchEvents(): HasMany
    {
        return $this->hasMany(BranchEvent::class);
    }
    
    public function supremeEntities(): HasMany
    {
        return $this->hasMany(SupremeEntity::class);
    }

    public function materialInstances(): HasMany
    {
        return $this->hasMany(MaterialInstance::class);
    }

    public function achievements(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Achievement\Entities\Achievement::class, 'universe_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function actors(): HasMany
    {
        return $this->hasMany(\App\Modules\Intelligence\Models\Actor::class);
    }
}
