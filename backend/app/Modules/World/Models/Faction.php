<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Faction extends Model
{
    protected $fillable = [
        'universe_id',
        'name',
        'culture_bias',
        'leader_id',
        'color',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_faction')
                    ->withPivot('loyalty');
    }
}
