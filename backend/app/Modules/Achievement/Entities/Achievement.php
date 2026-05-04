<?php

namespace App\Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'icon',
        'rarity',
        'conditions',
        'sort_order',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Intelligence\Models\Actor::class, 'actor_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function universes(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\World\Models\Universe::class, 'universe_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function claimers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\WorldOS\Models\User::class, 'user_claims')
            ->withPivot('claimed_at')
            ->withTimestamps();
    }
}
