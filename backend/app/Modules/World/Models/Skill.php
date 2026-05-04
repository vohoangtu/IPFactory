<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    protected $fillable = [
        'vocation_id',
        'name',
        'element',
        'cost',
        'rule_dsl',
        'metadata',
    ];

    protected $casts = [
        'element' => 'array',
        'metadata' => 'array',
    ];

    public function vocation(): BelongsTo
    {
        return $this->belongsTo(VocationDefinition::class, 'vocation_id', 'id');
    }
}
