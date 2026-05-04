<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Legend extends Model
{
    protected $fillable = [
        'actor_id',
        'legendary_agent_id',
        'title',
        'story',
        'power_score',
        'legend_level',
        'achievement_ids',
    ];

    protected $casts = [
        'power_score' => 'float',
        'achievement_ids' => 'array',
    ];

    public const LEVEL_HERO = 1;
    public const LEVEL_CHAMPION = 2;
    public const LEVEL_MYTHIC_HERO = 3;
    public const LEVEL_DEMIGOD = 4;
    public const LEVEL_GODLIKE = 5;

    public function legendaryAgent(): BelongsTo
    {
        return $this->belongsTo(LegendaryAgent::class);
    }
}
