<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Belief extends Model
{
    protected $fillable = [
        'name',
        'type',
        'trait_weights',
    ];

    protected $casts = [
        'trait_weights' => 'array',
    ];

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_beliefs')
            ->withPivot('alignment')
            ->withTimestamps();
    }
}
