<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;

class AiMemory extends Model
{
    protected $fillable = [
        'universe_id',
        'scope',
        'category',
        'keywords',
        'content',
        'embedding',
        'embedding_model',
        'embedding_version',
        'source',
        'importance',
        'expires_at',
        'content_hash',
    ];

    protected $casts = [
        'embedding' => 'array',
        'expires_at' => 'datetime',
    ];
}
