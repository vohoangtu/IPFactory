<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Domain\Phase\PhaseDetector;
use App\Modules\Intelligence\Domain\Society\SocietyMetricsCalculator;
use App\Modules\World\Models\Universe;

class PhaseMetricsComputer
{
    public function __construct(
        private SocietyMetricsCalculator $metricsCalculator,
        private PhaseDetector $phaseDetector,
    ) {}

    public function compute(Universe $universe, array $actorStates, array $ratios, int $tick): array
    {
        $polarization = $this->metricsCalculator->calculatePolarization($actorStates);
        $cohesion = $this->metricsCalculator->calculateSocialCohesion($actorStates, $polarization);
        $phase = $this->phaseDetector->detect($universe->entropy ?? 0.5, $polarization, $universe->level ?? 1);

        $historicalPhaseScores = ($universe->state_vector ?? [])['historical_phase_scores'] ?? [];
        $historicalPhaseScores[] = $phase->toArray();
        if (count($historicalPhaseScores) > 5) {
            array_shift($historicalPhaseScores);
        }

        $momentum = $this->metricsCalculator->calculateCulturalMomentum($historicalPhaseScores);

        $sv = $universe->state_vector ?? [];
        $sv['historical_phase_scores'] = $historicalPhaseScores;
        $universe->state_vector = $sv;

        return [
            'polarization' => $polarization,
            'cohesion' => $cohesion,
            'phase' => $phase,
            'momentum' => $momentum,
        ];
    }
}
