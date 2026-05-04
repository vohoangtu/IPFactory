<?php

namespace App\Modules\Simulation\Models;

use App\Modules\World\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Self-improving rule proposal (Phase 3 §3.3): proposed DSL, sandbox result, optional deploy.
 */
class RuleProposal extends Model
{
    protected $table = 'rule_proposals';

    protected $fillable = [
        'universe_id',
        'tick',
        'dsl',
        'sandbox_result',
        'version',
        'deployed_at',
        'engine_manifest_snapshot',
    ];

    protected $casts = [
        'sandbox_result' => 'array',
        'deployed_at' => 'datetime',
        'engine_manifest_snapshot' => 'array',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }
}
