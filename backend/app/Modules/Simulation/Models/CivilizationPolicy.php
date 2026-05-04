<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CivilizationPolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'generation', 'arena_batch_id', 'parent_policy_id',
        'survival_priority', 'stability_priority', 'diversity_priority',
        'fitness_score',
    ];

    protected $casts = [
        'survival_priority'  => 'float',
        'stability_priority' => 'float',
        'diversity_priority' => 'float',
        'fitness_score'      => 'float',
    ];

    public function arenaBatch()
    {
        return $this->belongsTo(ArenaBatch::class);
    }

    public function decisionModels()
    {
        return $this->hasMany(UniverseDecisionModel::class, 'policy_id');
    }
}
