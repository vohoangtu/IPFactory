<?php

namespace App\Modules\Narrative\Models;

use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturalArtifact extends Model
{
    use HasFactory;

    protected $table = 'cultural_artifacts';

    protected $fillable = [
        'universe_id',
        'civ_id',
        'author_id',
        'name',
        'type',
        'power_level',
        'properties',
        'created_at_tick',
        'is_active',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_active' => 'boolean',
        'power_level' => 'float',
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }
}
