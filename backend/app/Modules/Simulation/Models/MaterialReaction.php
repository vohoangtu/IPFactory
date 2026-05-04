<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'inputs',
        'outputs',
        'condition',
        'rate',
        'energy_cost',
        'entropy_produced',
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'rate' => 'float',
        'energy_cost' => 'float',
        'entropy_produced' => 'float',
    ];
}
