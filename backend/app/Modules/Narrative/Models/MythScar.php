<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MythScar extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_id',
        'zone_id',
        'name',
        'description',
        'severity',
        'decay_rate',
        'created_at_tick',
        'resolved_at_tick',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
