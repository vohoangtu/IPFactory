<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Myth extends Model
{
    protected $fillable = [
        'universe_id',
        'chronicle_id',
        'myth_type',
        'story',
        'source_events',
        'impact',
    ];

    protected $casts = [
        'source_events' => 'array',
        'impact' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function chronicle(): BelongsTo
    {
        return $this->belongsTo(Chronicle::class);
    }
}
