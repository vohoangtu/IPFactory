<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyVault extends Model
{
    protected $fillable = [
        'world_id', 'entity_name', 'entity_type', 'legacy_data', 
        'archived_at_tick', 'impact_score'
    ];

    protected $casts = [
        'legacy_data' => 'array',
        'impact_score' => 'float',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
