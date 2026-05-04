<?php

namespace App\Modules\Institutions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalEntity extends Model
{
    protected $fillable = [
        'universe_id', 'civilization_id', 'founder_actor_id', 'idea_id', 'name', 'entity_type', 'institution_type',
        'ideology_vector', 'org_capacity', 'institutional_memory', 'legitimacy',
        'influence_map', 'status', 'members', 'zone_id', 'spawned_at_tick', 'collapsed_at_tick',
    ];

    protected $casts = [
        'ideology_vector' => 'array',
        'influence_map' => 'array',
        'org_capacity' => 'float',
        'institutional_memory' => 'float',
        'legitimacy' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'founder_actor_id');
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function civilization(): BelongsTo
    {
        return $this->belongsTo(Civilization::class);
    }

    public function leaders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InstitutionLeader::class, 'institution_id');
    }

    /**
     * Check if entity should collapse (org_capacity <= 0).
     */
    public function isCollapsed(): bool
    {
        return $this->org_capacity <= 0 || $this->collapsed_at_tick !== null;
    }
}
