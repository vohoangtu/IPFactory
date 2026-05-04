<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when self-improving pipeline proposes a rule and sandbox test succeeds (Phase 3 §3.3).
 * Listeners can persist to rule versioning table or trigger deploy.
 */
class RuleProposed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $universeId,
        public int $tick,
        public string $dsl,
        public array $sandboxResult = []
    ) {}
}
