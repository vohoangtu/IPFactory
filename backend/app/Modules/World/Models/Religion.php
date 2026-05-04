<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Religion extends Model
{
    protected $fillable = [
        'universe_id',
        'name',
        'origin_myth_id',
        'founder_actor_id',
        'doctrine',
        'spread_rate',
        'followers',
        'holy_sites',
    ];

    protected $casts = [
        'spread_rate' => 'float',
        'followers' => 'integer',
        'holy_sites' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function originMyth(): BelongsTo
    {
        return $this->belongsTo(Myth::class, 'origin_myth_id');
    }

    public function actors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_religion')
            ->withPivot('believed_at_tick')
            ->withTimestamps();
    }
}
