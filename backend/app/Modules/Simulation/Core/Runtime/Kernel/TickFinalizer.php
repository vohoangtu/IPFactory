<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\Kernel;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

class TickFinalizer
{
    public function finalizeTick(WorldState $state, int $tick): void
    {
        // Periodic Decay of Memory to prevent state bloat
        if ($tick % 50 === 0) {
            app(\App\Modules\Simulation\Actions\DecayScarsAction::class)->handle((int)$state->get('universe_id'));
        }

        // Dispatch Domain Events
        $this->dispatchDomainEvents($state, $tick);
    }

    public function dispatchDomainEvents(WorldState $state, int $tick): void
    {
        $entropy = $state->get('entropy', 0.0);
        $stability = $state->get('stability_index', 1.0);
        $universeId = (int) $state->get('universe_id', 0);

        // Dispatch StabilityCompromised khi entropy > 0.8 hoặc stability < 0.2
        if ($entropy > 0.8 || $stability < 0.2) {
            event(new \App\Modules\Simulation\Core\Events\StabilityCompromised(
                universeId: $universeId,
                tick: $tick,
                entropy: (float) $entropy,
                stabilityIndex: (float) $stability,
                reason: $entropy > 0.8 ? 'high_entropy' : 'low_stability',
            ));
        }
    }

    public function processCausalImpacts(WorldState $state, int $tick, array $tickImpacts): void
    {
        if (empty($tickImpacts)) {
            return;
        }

        $allLinks = [];
        foreach ($tickImpacts as $report) {
            foreach ($report->links as $link) {
                $allLinks[] = $link;
            }
        }

        // V81 Quantum Branching: Collapse divergence into canon history
        $divergenceEngine = app(\App\Modules\Simulation\Core\Runtime\Engines\DivergenceEngine::class);
        $canonLinks = $divergenceEngine->collapse($allLinks, $state);

        $causalEngine = app(\App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine::class);
        foreach ($canonLinks as $link) {
            $causalEngine->recordLink($link, $tick);
        }
    }
}
