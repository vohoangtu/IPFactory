<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\RuleProposed;
use App\Modules\Simulation\Models\RuleProposal;

/**
 * Persist rule proposal to rule_proposals table when sandbox test succeeds (Phase 3 rule versioning).
 */
class PersistRuleProposal
{
    public function handle(RuleProposed $event): void
    {
        RuleProposal::create([
            'universe_id' => $event->universeId,
            'tick' => $event->tick,
            'dsl' => $event->dsl,
            'sandbox_result' => $event->sandboxResult,
        ]);
    }
}


