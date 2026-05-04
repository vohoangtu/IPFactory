<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Civilization extends Model
{
    protected $fillable = [
        'universe_id',
        'name',
        'origin_tick',
        'collapse_tick',
        'culture_group',
        'dominant_religion_id',
        'capital_zone_id',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function institutions(): HasMany
    {
        return $this->hasMany(InstitutionalEntity::class, 'civilization_id');
    }

    public function history(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CivilizationHistory::class, 'civilization_id');
    }
}
