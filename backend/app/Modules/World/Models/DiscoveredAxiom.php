<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoveredAxiom extends Model
{
    protected $fillable = [
        'universe_id', 'tick_discovered', 'axiom_key', 'description', 
        'hypothesized_effect', 'confidence', 'status'
    ];

    public function universe()
    {
        return $this->belongsTo(Universe::class);
    }
}
