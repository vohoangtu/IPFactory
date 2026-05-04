<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NarrativeSeries extends Model
{
    protected $fillable = [
        'universe_id',
        'title',
        'slug',
        'genre_key',
        'current_book_index',
        'total_chapters_generated',
        'status',
        'published_at',
        'description',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'current_book_index' => 'integer',
        'total_chapters_generated' => 'integer',
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(SerialChapter::class, 'series_id');
    }

    public function bible(): HasOne
    {
        return $this->hasOne(StoryBible::class, 'series_id');
    }
}
