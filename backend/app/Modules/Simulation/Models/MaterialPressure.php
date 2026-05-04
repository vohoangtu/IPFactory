<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPressure extends Model
{
    protected $fillable = ['material_id', 'vector_key', 'coefficient'];

    protected $casts = [
        'coefficient' => 'float',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
