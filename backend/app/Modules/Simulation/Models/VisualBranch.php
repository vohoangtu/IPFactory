<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;

class VisualBranch extends Model
{
    protected $fillable = [
        'legendary_agent_id',
        'parent_branch_id',
        'visual_dna',
        'fork_tick',
        'fork_reason',
    ];

    protected $casts = [
        'visual_dna' => 'array',
    ];

    public function agent()
    {
        return $this->belongsTo(LegendaryAgent::class, 'legendary_agent_id');
    }

    public function mutations()
    {
        return $this->hasMany(VisualMutation::class);
    }
}
