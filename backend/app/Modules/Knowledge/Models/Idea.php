<?php

namespace App\Modules\Knowledge\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    /** Doc §8: rumor | propaganda | science | religion | meme */
    public const INFO_TYPE_RUMOR = 'rumor';
    public const INFO_TYPE_PROPAGANDA = 'propaganda';
    public const INFO_TYPE_SCIENCE = 'science';
    public const INFO_TYPE_RELIGION = 'religion';
    public const INFO_TYPE_MEME = 'meme';

    protected $fillable = [
        'universe_id',
        'origin_actor_id',
        'artifact_id',
        'theme',
        'info_type',
        'influence_score',
        'followers',
        'birth_tick',
    ];

    protected $casts = [
        'influence_score' => 'float',
        'followers' => 'float',
        'birth_tick' => 'integer',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function originActor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'origin_actor_id');
    }

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
