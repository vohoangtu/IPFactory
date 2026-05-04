<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryBible extends Model
{
    protected $fillable = [
        'series_id',
        'characters',
        'locations',
        'lore',
    ];

    protected $casts = [
        'characters' => 'array',
        'locations' => 'array',
        'lore' => 'array',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(NarrativeSeries::class, 'series_id');
    }

    /**
     * Find a character by name (case-insensitive).
     */
    public function findCharacter(string $name): ?array
    {
        return collect($this->characters ?? [])->first(
            fn($c) => strcasecmp($c['name'] ?? '', $name) === 0
        );
    }

    /**
     * Upsert a character by name.
     */
    public function upsertCharacter(array $character): void
    {
        $chars = collect($this->characters ?? []);
        $idx = $chars->search(fn($c) => strcasecmp($c['name'] ?? '', $character['name'] ?? '') === 0);

        if ($idx !== false) {
            $chars[$idx] = array_merge($chars[$idx], $character);
        } else {
            $chars->push($character);
        }

        $this->characters = $chars->values()->toArray();
        $this->save();
    }
}
