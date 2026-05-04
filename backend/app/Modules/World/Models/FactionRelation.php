<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactionRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_faction_id',
        'to_faction_id',
        'type',
        'tension',
    ];

    /**
     * Get the faction that owns the relation (source).
     */
    public function fromFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'from_faction_id');
    }

    /**
     * Get the target faction.
     */
    public function toFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'to_faction_id');
    }
}
