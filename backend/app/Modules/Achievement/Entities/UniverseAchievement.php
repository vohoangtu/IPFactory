<?php

namespace App\Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UniverseAchievement extends Pivot
{
    protected $table = 'universe_achievements';

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public $timestamps = true;
}
