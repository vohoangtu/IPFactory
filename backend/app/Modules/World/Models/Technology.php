<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'requirements',
        'effects',
    ];

    protected $casts = [
        'requirements' => 'array',
        'effects' => 'array',
    ];

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_technologies')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function factions(): BelongsToMany
    {
        return $this->belongsToMany(Faction::class, 'faction_technologies')
            ->withPivot('unlock_status')
            ->withTimestamps();
    }
}
