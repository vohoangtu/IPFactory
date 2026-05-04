<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtradimensionalRelic extends Model
{
    protected $fillable = [
        'world_id', 'origin_universe_id', 'name',
        'rarity', 'power_vector', 'description', 'metadata'
    ];

    protected $casts = [
        'power_vector' => 'array',
        'metadata' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function originUniverse(): BelongsTo
    {
        return $this->belongsTo(Universe::class, 'origin_universe_id');
    }
}
