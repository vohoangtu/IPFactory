<?php

namespace App\Modules\Institutions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupremeEntity extends Model
{
    protected $fillable = [
        'universe_id',
        'actor_id',
        'name',
        'entity_type',
        'domain',
        'description',
        'power_level',
        'alignment',
        'karma',
        'karma_metadata',
        'status',
        'ascended_at_tick',
        'fallen_at_tick',
    ];

    protected $casts = [
        'alignment' => 'array',
        'karma_metadata' => 'array',
        'power_level' => 'float',
        'karma' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
