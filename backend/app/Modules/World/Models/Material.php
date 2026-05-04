<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    public const ONTOLOGY_PHYSICAL = 'physical';
    public const ONTOLOGY_INSTITUTIONAL = 'institutional';
    public const ONTOLOGY_SYMBOLIC = 'symbolic';
    public const ONTOLOGY_BEHAVIORAL = 'behavioral';

    public const LIFECYCLE_DORMANT = 'dormant';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_OBSOLETE = 'obsolete';

    protected $fillable = [
        'name', 'slug', 'description', 'ontology', 'lifecycle',
        'inputs', 'outputs', 'pressure_coefficients',
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'pressure_coefficients' => 'array',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(MaterialInstance::class);
    }

    public function pressures(): HasMany
    {
        return $this->hasMany(MaterialPressure::class);
    }

    /**
     * Reactions where this material is an input.
     */
    public function inputReactions()
    {
        return MaterialReaction::whereJsonContains('input_material_ids', $this->id)->get();
    }

    /**
     * Reactions where this material is an output.
     */
    public function outputReactions()
    {
        return MaterialReaction::whereJsonContains('output_material_ids', $this->id)->get();
    }
}
