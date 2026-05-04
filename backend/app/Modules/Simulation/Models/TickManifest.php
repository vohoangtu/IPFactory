<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TickManifest extends Model
{
    protected $table = 'simulation_tick_manifests';

    protected $fillable = [
        'universe_id',
        'tick',
        'seed',
        'engines_ran',
        'engines_skipped',
        'effects',
        'events',
        'state_diff',
        'elapsed_ms',
    ];

    protected $casts = [
        'engines_ran' => 'array',
        'engines_skipped' => 'array',
        'effects' => 'array',
        'events' => 'array',
        'state_diff' => 'array',
        'seed' => 'integer',
        'elapsed_ms' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
