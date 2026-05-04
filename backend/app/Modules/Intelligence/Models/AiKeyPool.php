<?php

namespace App\Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiKeyPool extends Model
{
    use HasFactory;

    protected $table = 'ai_key_pool';

    protected $fillable = [
        'provider',
        'label',
        'key_encrypted',
        'model_group',
        'tier',
        'level',
        'is_free',
        'usage_count',
        'status',
        'last_used_at',
        'cooldown_until',
        'metadata',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'level' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'cooldown_until' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'key_encrypted',
    ];

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                  ->orWhere('cooldown_until', '<=', now());
            });
    }
}
