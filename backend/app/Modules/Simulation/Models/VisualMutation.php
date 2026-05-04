<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;

class VisualMutation extends Model
{
    protected $fillable = [
        'visual_branch_id',
        'type',
        'severity',
        'modifiers',
        'trigger_event',
        'tick',
    ];

    protected $casts = [
        'modifiers' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(VisualBranch::class);
    }
}
