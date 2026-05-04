<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\Causality\CausalLink;
use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Actions\UpdateCollectiveUnconsciousAction;

/**
 * System to update the Collective Unconscious field during a simulation pulse.
 * Phase 80: Mind Layer - RULE_ATTRACTION.
 */
class CollectiveUnconsciousSystem implements WorldSystemInterface
{
    public function __construct(
        private readonly UpdateCollectiveUnconsciousAction $action
    ) {}

    /**
     * Update the collective unconscious based on actor profiles.
     * We run this every 5 ticks to optimize performance.
     */
    public function update(array $context, int $tick): ?ImpactReport
    {
        if ($tick % 5 !== 0) {
            return null;
        }

        $universeId = (int) ($context['universe_id'] ?? 0);
        if (!$universeId) return null;

        $universe = Universe::find($universeId);
        if (!$universe) return null;

        // Effect-first: compute mutation, report it, let WorldKernel apply it.
        $oldVector = $universe->state_vector['collective_unconscious'] ?? [];
        $newVector = $this->action->calculate($universe);

        if (! $this->action->shouldMutate($oldVector, $newVector)) {
            return null;
        }

        $report = new ImpactReport(
            'CollectiveUnconsciousSystem',
            WorldKernel::PHASE_MIND,
            WorldKernel::RULE_ATTRACTION,
            'Effect-first mutation for collective unconscious'
        );

        $report->addLink(new CausalLink(
            sourceType: 'universe',
            sourceId: $universeId,
            relation: 'reflects',
            targetType: 'collective_unconscious',
            targetId: $universeId,
            magnitude: 0.1,
            probability: 1.0,
            metadata: [
                'tick' => $tick,
                'mutation' => [
                    'collective_unconscious' => $newVector,
                ],
            ]
        ));

        return $report;
    }
}

