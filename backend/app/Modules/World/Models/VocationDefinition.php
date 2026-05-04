<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VocationDefinition extends Model
{
    protected $table = 'vocation_definitions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'tier',
        'element_affinity',
        'requirements',
        'evolves_to',
        'tags',
        'motivation_profile',
    ];

    protected $casts = [
        'element_affinity' => 'array',
        'requirements' => 'array',
        'tags' => 'array',
        'motivation_profile' => 'array',
    ];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'vocation_id', 'id');
    }
}
