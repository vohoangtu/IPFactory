<?php
namespace App\Modules\Simulation\Actions\PhaseRunners;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Ecology\PressureCalculator;
use App\Modules\Simulation\Services\Cosmology\CosmicPhaseDetector;
use App\Modules\Simulation\Services\Cosmology\CosmicEnergyPoolService;
use App\Modules\Intelligence\Services\BiologyMetricsService;
use App\Modules\Intelligence\Services\EcosystemMetricsService;
use App\Modules\Simulation\Services\Culture\GenreEvolutionService;
use App\Modules\Simulation\Services\Meta\WorldRegulatorEngine;
use App\Modules\Simulation\Services\Meta\MultiverseInteractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class CalculateMetricsPhaseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected PressureCalculator $pressureCalculator,
        protected CosmicPhaseDetector $cosmicPhaseDetector,
        protected CosmicEnergyPoolService $cosmicEnergyPoolService,
        protected BiologyMetricsService $biologyMetrics,
        protected EcosystemMetricsService $ecosystemMetrics,
        protected GenreEvolutionService $genreEvolutionService,
        protected WorldRegulatorEngine $worldRegulatorEngine,
        protected MultiverseInteractionService $multiverseInteractionService
    ) {}

    public function execute(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // 1. Calculate & Store Pressure Metrics
        $this->storePressureMetrics($universe, $snapshot);

        // 2. Power Economy: cosmic energy pool (after metrics final)
        $this->cosmicEnergyPoolService->processPulse($universe, $snapshot);
        $universe->refresh();

        // 3. Detect & Dispatch Anomalies
        $this->detectAnomalies($universe, $snapshot);

        // 4. Multiverse Interaction
        $this->multiverseInteractionService->detectResonance($universe);

        // 5. World Autonomic Regulation
        if ($universe->world) {
            $this->worldRegulatorEngine->process($universe->world);
            $this->genreEvolutionService->evaluateEvolution($universe);
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
        ];
        $metrics = array_replace_recursive($snapshot->metrics ?? [], $calculated_metrics);

        $metrics = $this->clampMetricsToUnitInterval($metrics);
        if (isset($snapshot->entropy)) {
            $snapshot->entropy = max(0.0, min(1.0, (float) $snapshot->entropy));
        }

        $metrics['cosmic_phase'] = $this->cosmicPhaseDetector->detect($snapshot, $metrics);

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

    protected function detectAnomalies($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        $stability = (float) $snapshot->stability_index;

        if ($entropy > 0.95) {
            Event::dispatch(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
                'title' => 'Cánh cửa Hư vô (Void Gate) Mở ra',
                'description' => 'Entropy đạt mức tới hạn ('.round($entropy*100, 2).'%). Cấu trúc thực tại đang tan biến.',
                'severity' => 'CRITICAL'
            ]));
        } elseif ($stability < 0.2) {
             Event::dispatch(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
                'title' => 'Sụp đổ Cấu trúc Xã hội',
                'description' => 'Chỉ số ổn định thấp kỷ lục ('.round($stability, 4).'). Các định chế đang tan rã.',
                'severity' => 'CRITICAL'
            ]));
        } elseif (($snapshot->metrics['material_stress'] ?? 0) > 0.8) {
             Event::dispatch(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
                'title' => 'Căng thẳng Vật chất Cực độ',
                'description' => 'Áp lực lên hạ tầng vượt ngưỡng an toàn. Nguy cơ ly khai diện rộng.',
                'severity' => 'WARN'
            ]));
        }
    }
}
