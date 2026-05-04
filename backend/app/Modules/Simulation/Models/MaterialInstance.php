<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialInstance extends Model
{
    protected $fillable = [
        'material_id', 'universe_id', 'lifecycle', 'activated_at_tick', 'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MaterialLog::class);
    }
}
