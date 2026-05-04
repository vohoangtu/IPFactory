<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ArenaBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'generation', 'universe_count', 'ticks_per_universe',
        'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function universes()
    {
        return $this->hasMany(Universe::class, 'arena_batch_id');
    }
}
