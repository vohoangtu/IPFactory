<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Narrative – Represents a collective interpretation of events in the world.
 * 
 * Narratives act as state transformers and reality filters.
 */
class Narrative extends Model
{
    protected $fillable = [
        'universe_id',
        'tick_born',
        'story',
        'virality',
        'distortion',
        'field_effects',
        'tags',
        'is_active',
        'source_event_id',
        'news_headline',
        'news_slogan',
        'vfx_config'
    ];

    protected $casts = [
        'field_effects' => 'array',
        'tags' => 'array',
        'virality' => 'float',
        'distortion' => 'float',
        'is_active' => 'boolean',
        'vfx_config' => 'array'
    ];
}
