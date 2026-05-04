<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniverseSnapshot extends Model
{
    protected $fillable = [
        'universe_id', 'tick', 'state_vector', 'entropy', 'stability_index', 'metrics',
    ];

    protected $casts = [
        'state_vector' => 'array',
        'metrics' => 'array',
        'entropy' => 'float',
        'stability_index' => 'float',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    // --- Domain Logic ---

    public function isCritical(): bool
    {
        return $this->entropy >= 0.85;
    }

    public function isUnstable(): bool
    {
        return $this->stability_index <= 0.25;
    }

    public function getMetric(string $key, $default = null)
    {
        return $this->metrics[$key] ?? $default;
    }

    public function getSummary(): string
    {
        return "Tick {$this->tick}: Entropy=" . number_format($this->entropy, 2) . ", Stability=" . number_format($this->stability_index, 2);
    }
}
