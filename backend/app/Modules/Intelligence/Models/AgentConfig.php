<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AgentConfig extends Model
{
    protected $fillable = [
        'agent_name',
        'personality',
        'creativity',
        'themes',
        'model_type',
        'local_endpoint',
        'model_name',
        'api_key',
        'historian_profile_id',
    ];

    protected $casts = [
        'themes' => 'array',
        'creativity' => 'integer'
    ];

    public function historianProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(HistorianProfile::class, 'historian_profile_id');
    }
}
