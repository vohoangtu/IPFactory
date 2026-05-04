<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class ActorMastery extends Model
{
    protected $table = 'actor_mastery';

    protected $fillable = [
        'actor_id',
        'skill_id',
        'level',
        'experience',
    ];

    public function actor()
    {
        return $this->belongsTo(Actor::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
