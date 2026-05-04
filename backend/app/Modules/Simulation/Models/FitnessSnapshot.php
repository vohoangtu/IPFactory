<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;

class FitnessSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'universe_id', 'arena_batch_id', 'tick',
        'survival_score', 'stability_score', 'diversity_score',
        'complexity_penalty', 'fitness_total', 'measured_at',
    ];

    protected $casts = [
        'survival_score'    => 'float',
        'stability_score'   => 'float',
        'diversity_score'   => 'float',
        'complexity_penalty' => 'float',
        'fitness_total'     => 'float',
        'measured_at'       => 'datetime',
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }
}
