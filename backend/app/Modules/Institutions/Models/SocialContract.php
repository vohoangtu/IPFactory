<?php

namespace App\Modules\Institutions\Models;

use Illuminate\Database\Eloquent\Model;

class SocialContract extends Model
{
    protected $fillable = [
        'universe_id',
        'type',
        'participants',
        'strictness',
        'duration',
        'created_at_tick',
        'expires_at_tick',
        'institutional_entity_id',
    ];

    protected $casts = [
        'participants' => 'array',
        'strictness' => 'float',
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }

    public function institutionalEntity()
    {
        return $this->belongsTo(InstitutionalEntity::class);
    }
}
