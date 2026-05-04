<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\World\Services\MaterialReactionEngine;
use App\Modules\World\Services\PressureResolver;
use App\Modules\World\Models\Universe;

/**
 * MaterialWorldSystem (V6): Orchestrates Material Evolution & Environmental Stress in the WorldKernel.
 */
class MaterialWorldSystem implements WorldSystemInterface
{
    public function __construct(
        protected MaterialReactionEngine $reactionEngine,
        protected PressureResolver $pressureResolver
    ) {}

    public function update(array $context, int $tick): ?ImpactReport
    {
        $state = app(\App\Modules\Simulation\Core\Runtime\State\StateManager::class)->get();
        if (!$state) return null;

        $universeId = $state->getUniverseId();
        $universe = Universe::find($universeId);
        if (!$universe) return null;

        // 1. Process Material Reactions (Multi-input/output DSL driven)
        $this->reactionEngine->process($state);

        // 2. Compute Environmental Stress feedback
        $zones = $state->getZones();
        foreach ($zones as &$zone) {
            $stress = $this->pressureResolver->resolve($zone, $state);
            $zone['state']['material_stress'] = round($stress, 3);
        }
        $state->setZones($zones);

        // 3. Generate Impact Report
        $report = new ImpactReport(
            'material_evolution',
            \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
            \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
            'Processed material lifecycle and environmental stress.'
        );

        foreach ($zones as $zone) {
            $report->log('zone', $zone['id'], 'has_stress', 'value', $zone['state']['material_stress']);
        }

        return $report;
    }
}
