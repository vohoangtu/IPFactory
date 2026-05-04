<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Simulation\Services\Ecology\PressureCalculator;
use App\Modules\Simulation\Services\Cosmology\CosmicPhaseDetector;
use App\Modules\Simulation\Services\Cosmology\CosmicEnergyPoolService;
use App\Modules\Simulation\Core\Engines\Meta\AttractorEngine;
use App\Modules\Simulation\Core\Engines\Meta\DynamicAttractorEngine;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use Illuminate\Support\Facades\Log;

/**
 * UpdateCivilizationMetrics — Phân rã từ EvaluateSimulationResult.
 * Chịu trách nhiệm tính toán và lưu trữ các chỉ số áp lực, entropy và attractors.
 */
class UpdateCivilizationMetrics
{
    public function __construct(
        protected PressureCalculator $pressureCalculator,
        protected CosmicPhaseDetector $cosmicPhaseDetector,
        protected CosmicEnergyPoolService $cosmicEnergyPoolService,
        protected AttractorEngine $attractorEngine,
        protected DynamicAttractorEngine $dynamicAttractorEngine,
        protected \App\Modules\Intelligence\Services\BiologyMetricsService $biologyMetrics,
        protected \App\Modules\Intelligence\Services\EcosystemMetricsService $ecosystemMetrics,
        protected \App\Modules\Simulation\Services\Civilization\MaterialIdentityProjector $materialIdentityProjector,
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;
        $rng = new SimulationRandom((int) ($universe->seed ?? 0), (int) $snapshot->tick, 0);

        try {
            // 1. Attractor fields
            $this->attractorEngine->evaluate($universe, $snapshot);
            $universe->refresh();

            $this->dynamicAttractorEngine->process($universe, $snapshot, $rng);
            $universe->refresh();

            // 2. Metrics & Pressure
            $this->storePressureMetrics($universe, $snapshot);

            // 3. Power Economy
            $this->cosmicEnergyPoolService->processPulse($universe, $snapshot);
            $universe->refresh();

        } catch (\Throwable $e) {
            Log::error("UpdateCivilizationMetrics failed: " . $e->getMessage(), [
                'universe_id' => $universe->id,
                'tick' => $snapshot->tick
            ]);
        }
    }

    protected function storePressureMetrics($universe, $snapshot): void
    {
        $state = $snapshot->state_vector ?? [];
        if (!isset($state['entropy'])) {
            $state['entropy'] = $snapshot->entropy ?? 0;
        }
        if (!isset($state['stability_index'])) {
            $state['stability_index'] = $snapshot->stability_index ?? 0;
        }

        $stress = $this->pressureCalculator->calculateMaterialStress($state);
        $cosmic = $this->pressureCalculator->calculateCosmicMetrics($state);

        $bio = $this->biologyMetrics->forUniverse($universe->id);
        $eco = $this->ecosystemMetrics->forUniverse($universe);

        $calculated_metrics = [
            'material_stress' => $stress,
            'order' => $cosmic['order'],
            'energy_level' => $cosmic['energy_level'],
            'actor_count' => $bio['total_alive'] ?? 0,
            'total_population' => $eco['total_population'] ?? 0,
            'ecosystem_metrics' => $eco,
            'material_identity' => $this->materialIdentityProjector->projectFromState($state),
        ];
        
        $metrics = array_replace_recursive($snapshot->metrics ?? [], $calculated_metrics);
        $metrics = $this->clampMetricsToUnitInterval($metrics);

        if (isset($snapshot->entropy)) {
            $snapshot->entropy = max(0.0, min(1.0, (float) $snapshot->entropy));
        }

        $metrics['cosmic_phase'] = $this->cosmicPhaseDetector->detect($snapshot, $metrics);

        // Update snapshot metrics directly
        if (!$snapshot->exists) {
            $latest = \App\Modules\Simulation\Models\UniverseSnapshot::where('universe_id', $universe->id)
                ->orderByDesc('tick')
                ->first();
            if ($latest) {
                $latest->metrics = array_merge($latest->metrics ?? [], $metrics);
                $latest->save();
            }
            return;
        }

        $snapshot->metrics = $metrics;
        $snapshot->save();
    }

    protected function clampMetricsToUnitInterval(array $metrics): array
    {
        $scalarKeys = ['material_stress', 'order', 'energy_level'];
        foreach ($scalarKeys as $key) {
            if (isset($metrics[$key])) {
                $metrics[$key] = max(0.0, min(1.0, (float) $metrics[$key]));
            }
        }
        if (isset($metrics['ethos']) && is_array($metrics['ethos'])) {
            foreach (['spirituality', 'openness', 'rationality', 'hardtech'] as $dim) {
                if (isset($metrics['ethos'][$dim])) {
                    $metrics['ethos'][$dim] = max(0.0, min(1.0, (float) $metrics['ethos'][$dim]));
                }
            }
        }
        return $metrics;
    }
}
