<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiplomaticTreaty extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'source_civ_id',
        'target_civ_id',
        'treaty_type', // 'ALLIANCE', 'NON_AGGRESSION', 'TRADE_AGREEMENT', 'VASSALAGE', 'EMBARGO'
        'terms',
        'started_at_tick',
        'ends_at_tick',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'terms' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
