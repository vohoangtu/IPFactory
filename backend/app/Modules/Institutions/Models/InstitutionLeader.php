<?php

namespace App\Modules\Institutions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionLeader extends Model
{
    protected $table = 'institution_leaders';

    protected $fillable = [
        'institution_id',
        'actor_id',
        'start_tick',
        'end_tick',
    ];

    protected $casts = [
        'start_tick' => 'integer',
        'end_tick' => 'integer',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(InstitutionalEntity::class, 'institution_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
