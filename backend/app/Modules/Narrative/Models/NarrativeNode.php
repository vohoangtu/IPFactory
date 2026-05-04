<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NarrativeNode extends Model
{
    protected $fillable = [
        'node_type',
        'ref_type',
        'ref_id',
        'universe_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function edgesFrom(): HasMany
    {
        return $this->hasMany(NarrativeEdge::class, 'from_node_id');
    }

    public function edgesTo(): HasMany
    {
        return $this->hasMany(NarrativeEdge::class, 'to_node_id');
    }
}
