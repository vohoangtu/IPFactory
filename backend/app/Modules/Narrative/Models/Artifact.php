<?php

namespace App\Modules\Narrative\Models;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Artifact extends Model
{
    protected $fillable = [
        'universe_id',
        'creator_actor_id',
        'institution_id',
        'artifact_type',
        'title',
        'theme',
        'culture',
        'tick_created',
        'impact_score',
        'metadata',
    ];

    protected $casts = [
        'tick_created' => 'integer',
        'impact_score' => 'float',
        'metadata' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'creator_actor_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(InstitutionalEntity::class, 'institution_id');
    }

    public function idea(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Idea::class);
    }
}
