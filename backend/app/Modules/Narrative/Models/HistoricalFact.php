<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalFact extends Model
{
    protected $table = 'historical_facts';

    protected $fillable = [
        'world_event_id',
        'universe_id',
        'tick',
        'year',
        'zone_id',
        'civilization_id',
        'category',
        'actors',
        'institutions',
        'metrics_before',
        'metrics_after',
        'facts',
        'parent_id',
    ];

    protected $casts = [
        'actors' => 'array',
        'institutions' => 'array',
        'metrics_before' => 'array',
        'metrics_after' => 'array',
        'facts' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
