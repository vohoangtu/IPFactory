<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CivilizationHistory extends Model
{
    protected $table = 'civilizations_history';

    protected $fillable = [
        'civilization_id',
        'origin_story',
        'golden_age_story',
        'collapse_story',
    ];

    public function civilization(): BelongsTo
    {
        return $this->belongsTo(Civilization::class);
    }
}
