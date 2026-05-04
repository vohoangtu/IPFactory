<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarrativeJob extends Model
{
    protected $fillable = [
        'universe_id',
        'engine',
        'payload',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
