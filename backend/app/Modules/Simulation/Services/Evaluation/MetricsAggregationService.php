<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Evaluation;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Services\BiologyMetricsService;
use App\Modules\Intelligence\Services\EcosystemMetricsService;
use App\Modules\Simulation\Services\Civilization\MaterialIdentityProjector;
use App\Modules\Simulation\Services\Cosmology\CosmicPhaseDetector;
use App\Modules\Simulation\Services\Ecology\PressureCalculator;

class MetricsAggregationService
{
    public function __construct(
        protected PressureCalculator $pressureCalculator,
        protected CosmicPhaseDetector $cosmicPhaseDetector,
        protected BiologyMetricsService $biologyMetrics,
        protected EcosystemMetricsService $ecosystemMetrics,
        protected MaterialIdentityProjector $materialIdentityProjector,
    ) {
    }

    public function storePressureMetrics(Universe $universe, $snapshot): void
    {
        $state = $snapshot->state_vector ?? [];
        // Đưa entropy/stability từ snapshot vào state để PressureCalculator dùng (fallback energy_level).
        if (! isset($state['entropy'])) {
            $state['entropy'] = $snapshot->entropy ?? 0;
        }
        if (! isset($state['stability_index'])) {
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
        // Merge: snapshot->metrics (cosmic impact from SupremeEntity) wins over calculated pressure.
        $metrics = array_replace_recursive($snapshot->metrics ?? [], $calculated_metrics);

        // Metrics invariant [0,1]: clamp when writing so downstream engines can trust values.
        $metrics = $this->clampMetricsToUnitInterval($metrics);
        if (isset($snapshot->entropy)) {
            $snapshot->entropy = max(0.0, min(1.0, (float) $snapshot->entropy));
        }

        // Cosmic phase (dominant axis + hysteresis)
        $metrics['cosmic_phase'] = $this->cosmicPhaseDetector->detect($snapshot, $metrics);

        // Snapshot ảo (chưa lưu DB): cập nhật metrics vào bản ghi snapshot mới nhất để dashboard có số liệu gần đúng.
        if (! $snapshot->exists) {
            $latest = UniverseSnapshot::where('universe_id', $universe->id)
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

    /**
     * Enforce metrics invariant [0,1] when writing. Clamp known scalar keys and ethos dimensions.
     */
    public function clampMetricsToUnitInterval(array $metrics): array
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
