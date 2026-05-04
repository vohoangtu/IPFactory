<?php

namespace App\Modules\Narrative\Models;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chronicle extends Model
{
    protected $fillable = [
        'universe_id', 'parent_id', 'actor_id', 'world_event_id', 'from_tick', 'to_tick', 'type', 'content', 'animation_script', 'importance',
        'perceived_archive_snapshot', 'raw_payload'
    ];

    protected $casts = [
        'perceived_archive_snapshot' => 'array',
        'raw_payload' => 'array',
        'animation_script' => 'array',
        'importance' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Chronicle::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Chronicle::class, 'parent_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
