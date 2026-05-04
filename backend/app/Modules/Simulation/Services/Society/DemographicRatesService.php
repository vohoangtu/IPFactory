<?php

namespace App\Modules\Simulation\Services\Society;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Ecology\DemographicStages;
use Illuminate\Support\Facades\Log;

/**
 * Doc §13: Birth/death rates from demographic stage.
 * Derives stage from knowledge/urban proxy, then assigns birth_rate and death_rate per stage.
 * Writes state_vector.civilization.demographic (stage, birth_rate, death_rate).
 */
class DemographicRatesService
{
    /** Default (birth_rate, death_rate) per stage when no historical rates. */
    private const RATES_BY_STAGE = [
        DemographicStages::STAGE_1_HIGH_BIRTH_HIGH_DEATH => [0.04, 0.03],
        DemographicStages::STAGE_2_HIGH_BIRTH_LOWER_DEATH => [0.03, 0.015],
        DemographicStages::STAGE_3_LOWER_BIRTH_LOW_DEATH => [0.015, 0.01],
        DemographicStages::STAGE_4_AGING_SOCIETY => [0.01, 0.012],
    ];

    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    public function evaluate(Universe $universe, int $currentTick): void
    {
        if (config('worldos.simulation.rust_authoritative', false)) {
            $stateVector = $this->getStateVector($universe);
            if (isset($stateVector['civilization']['demographic'])) {
                return;
            }
        }

        $stateVector = $this->getStateVector($universe);
        $settlements = $stateVector['civilization']['settlements'] ?? [];
        $knowledge = (float) ($stateVector['fields']['knowledge'] ?? $stateVector['knowledge'] ?? 0.3);
        $urbanRatio = $this->urbanRatioFromSettlements($settlements);

        $stage = $this->inferStage($knowledge, $urbanRatio);
        [$birthRate, $deathRate] = self::RATES_BY_STAGE[$stage] ?? [0.02, 0.015];

        $stateVector['civilization']['demographic'] = [
            'stage' => $stage,
            'birth_rate' => round($birthRate, 4),
            'death_rate' => round($deathRate, 4),
            'urban_ratio_proxy' => round($urbanRatio, 4),
            'updated_tick' => $currentTick,
        ];
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        Log::debug("DemographicRatesService: Universe {$universe->id} demographic updated at tick {$currentTick}");
    }

    private function urbanRatioFromSettlements(array $settlements): float
    {
        if (empty($settlements)) {
            return 0.0;
        }
        $urban = 0;
        foreach ($settlements as $s) {
            $level = (string) ($s['level'] ?? 'camp');
            if (in_array($level, ['town', 'city'], true)) {
                $urban++;
            }
        }
        return min(1.0, $urban / count($settlements));
    }

    private function inferStage(float $knowledge, float $urbanRatio): string
    {
        if ($knowledge < 0.25 && $urbanRatio < 0.2) {
            return DemographicStages::STAGE_1_HIGH_BIRTH_HIGH_DEATH;
        }
        if ($knowledge < 0.5 && $urbanRatio < 0.5) {
            return DemographicStages::STAGE_2_HIGH_BIRTH_LOWER_DEATH;
        }
        if ($knowledge < 0.75 && $urbanRatio < 0.7) {
            return DemographicStages::STAGE_3_LOWER_BIRTH_LOW_DEATH;
        }
        return DemographicStages::STAGE_4_AGING_SOCIETY;
    }

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }
}

