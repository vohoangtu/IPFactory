<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleSetDefinition extends Model
{
    protected $table = 'ruleset_definitions';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'tier_id',
        'name',
        'extends',
        'physics',
        'energy',
        'metaphysics',
        'power_law',
        'social',
        'is_locked'
    ];

    protected $casts = [
        'physics' => 'array',
        'energy' => 'array',
        'metaphysics' => 'array',
        'power_law' => 'array',
        'social' => 'array',
        'is_locked' => 'boolean'
    ];

    public function tier()
    {
        return $this->belongsTo(RuleSetTier::class, 'tier_id');
    }

    public function worlds()
    {
        return $this->hasMany(World::class, 'primary_ruleset_id');
    }
}
