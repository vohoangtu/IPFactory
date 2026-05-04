<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;

class UniverseBridge extends Model
{
    protected $fillable = [
        'source_universe_id',
        'target_universe_id',
        'bridge_type',
        'resonance_level',
        'is_active',
        'convergence_score',
        'last_synced_tick',
    ];

    public function sourceUniverse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Universe::class, 'source_universe_id');
    }

    public function targetUniverse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Universe::class, 'target_universe_id');
    }
}
