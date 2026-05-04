<?php

namespace App\Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ActorAchievement extends Pivot
{
    protected $table = 'actor_achievements';

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public $timestamps = true;
}
