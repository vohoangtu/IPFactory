<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialLog extends Model
{
    protected $fillable = ['material_instance_id', 'event', 'tick', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function materialInstance(): BelongsTo
    {
        return $this->belongsTo(MaterialInstance::class);
    }
}
