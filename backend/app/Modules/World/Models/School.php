<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class School extends Model
{
    protected $fillable = [
        'universe_id',
        'founder_actor_id',
        'idea_id',
        'name',
        'members',
        'influence',
        'status',
    ];

    protected $casts = [
        'members' => 'integer',
        'influence' => 'float',
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
}
