<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniverseDecisionModel extends Model
{
    use HasUuids;

    protected $table = 'universe_decision_models';

    protected $fillable = [
        'id', 'universe_id', 'policy_id', 'model_type',
        'weight_vector', 'interaction_matrix', 'threshold_vector',
        'context_weights', 'generation',
    ];

    protected $casts = [
        'weight_vector'      => 'array',
        'interaction_matrix' => 'array',
        'threshold_vector'   => 'array',
        'context_weights'    => 'array',
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }

    public function policy()
    {
        return $this->belongsTo(CivilizationPolicy::class, 'policy_id');
    }
}
