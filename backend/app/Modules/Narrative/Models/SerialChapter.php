<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialChapter extends Model
{
    protected $fillable = [
        'series_id',
        'chronicle_id',
        'book_index',
        'chapter_index',
        'title',
        'content',
        'tick_start',
        'tick_end',
        'needs_review',
        'canonized_at',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
        'canonized_at' => 'datetime',
        'tick_start' => 'integer',
        'tick_end' => 'integer',
        'book_index' => 'integer',
        'chapter_index' => 'integer',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(NarrativeSeries::class, 'series_id');
    }

    public function chronicle(): BelongsTo
    {
        return $this->belongsTo(Chronicle::class);
    }

    public function isCanonized(): bool
    {
        return $this->canonized_at !== null;
    }
}
