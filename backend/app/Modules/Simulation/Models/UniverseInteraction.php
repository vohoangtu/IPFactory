<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;

class UniverseInteraction extends Model
{
    protected $fillable = [
        'universe_a_id',
        'universe_b_id',
        'interaction_type',
        'payload',
        'resonance_level',
        'synchronicity_score',
    ];

    protected $casts = [
        'payload' => 'array',
        'resonance_level' => 'float',
        'synchronicity_score' => 'float',
    ];

    public function universeA()
    {
        return $this->belongsTo(Universe::class, 'universe_a_id');
    }

    public function universeB()
    {
        return $this->belongsTo(Universe::class, 'universe_b_id');
    }
}
