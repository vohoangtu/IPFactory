<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class LegendaryAgent extends Model
{
    protected $fillable = [
        'universe_id',
        'original_agent_id',
        'alignment_id',
        'name',
        'archetype',
        'fate_tags',
        'biography',
        'image_url',
        'tick_discovered',
        'is_transcendental',
        'soul_metadata',
        'heresy_score',
        'is_isekai',
    ];

    public function alignment()
    {
        return $this->belongsTo(Demiurge::class, 'alignment_id');
    }

    protected $casts = [
        'fate_tags' => 'array',
        'is_isekai' => 'boolean',
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }
}
