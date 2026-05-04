<?php

namespace App\Modules\Simulation\Services\Politics;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

/**
 * Civilization Discovery Engine (Doc §36): genome, evolutionary search, fitness evaluation.
 * Writes state_vector.civilization.discovery.fitness when run every N ticks.
 * Phase 3: runGeneration includes optional crossover (merge state of two parents) + mutate.
 */
final class CivilizationDiscoveryService
{
    public const GOVERNANCE_TYPES = ['tribal', 'chiefdom', 'kingdom', 'republic', 'federation'];
    public const ECONOMIC_TYPES = ['subsistence', 'trade', 'industrial', 'knowledge'];
    public const BELIEF_TYPES = ['animist', 'theistic', 'philosophical', 'secular'];

    public function __construct(
        protected UniverseRepositoryInterface $universeRepository,
        protected ImplicitOrchestratorService $orchestrator,
    ) {}

    public function fitness(
        float $lifespanYears,
        float $innovationRate,
        float $populationPeak,
        float $stabilityScore,
        float $culturalRichness
    ): float {
        return $lifespanYears * 0.3
            + $innovationRate * 100 * 0.2
            + $populationPeak * 0.2
            + $stabilityScore * 100 * 0.2
            + $culturalRichness * 0.1;
    }

    /**
     * Compute discovery fitness from universe/snapshot and write to state_vector.civilization.discovery.
     */
    public function evaluate(Universe $universe, int $currentTick, ?UniverseSnapshot $snapshot = null): void
    {
        $interval = (int) config('worldos.civilization_discovery.fitness_interval', 10);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        $universe->refresh();
        $stateVector = $this->getStateVector($universe);
        $ticksPerYear = max(1, (int) config('worldos.intelligence.ticks_per_year', config('worldos.emergence.ticks_per_year', 12)));
        $lifespanYears = $currentTick / $ticksPerYear;
        $innovationRate = (float) ($stateVector['innovation'] ?? $stateVector['knowledge_core'] ?? 0.5);
        $economy = $stateVector['civilization']['economy'] ?? [];
        $populationPeak = (float) ($economy['total_surplus'] ?? 0) + (float) ($economy['total_consumption'] ?? 0);
        if ($populationPeak <= 0) {
            $settlements = $stateVector['civilization']['settlements'] ?? [];
            foreach ($settlements as $s) {
                $populationPeak += (float) ($s['population'] ?? 0);
            }
        }
        $populationPeak = max(0, $populationPeak);
        $stabilityScore = $snapshot ? (float) $snapshot->stability_index : (float) ($stateVector['stability_index'] ?? $stateVector['sci'] ?? 0.5);
        $culturalRichness = (float) ($stateVector['cognitive_aggregate']['civilization_tendency'] ?? 0.5);
        if (isset($stateVector['knowledge_graph']['nodes'])) {
            $culturalRichness = min(1.0, 0.3 + 0.1 * count($stateVector['knowledge_graph']['nodes']));
        }

        $fitnessValue = $this->fitness($lifespanYears, $innovationRate, $populationPeak, $stabilityScore, $culturalRichness);
        $civilization = $stateVector['civilization'] ?? [];
        $civilization['discovery'] = [
            'fitness' => round($fitnessValue, 4),
            'updated_tick' => $currentTick,
        ];
        $stateVector['civilization'] = $civilization;
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        Log::debug("CivilizationDiscoveryService: Universe {$universe->id} discovery.fitness={$fitnessValue} at tick {$currentTick}");
    }

    /**
     * Run one GA generation: evaluate fitness, selection (top-k), optional crossover+mutate (Phase 3 §3.3).
     *
     * @param  array<int>  $universeIds
     * @return array{evaluated: array<int, float>, selected: array<int>, next_generation: array<int>}
     */
    public function runGeneration(array $universeIds): array
    {
        if (empty($universeIds)) {
            return ['evaluated' => [], 'selected' => [], 'next_generation' => []];
        }

        $fitnessMap = [];
        foreach ($universeIds as $id) {
            $universe = $this->universeRepository->find($id);
            if (! $universe) {
                continue;
            }
            $vec = $this->getStateVector($universe);
            $discovery = $vec['civilization']['discovery'] ?? null;
            $fitness = isset($discovery['fitness']) ? (float) $discovery['fitness'] : 0.0;
            $fitnessMap[$id] = $fitness;
        }

        $topK = (int) config('worldos.civilization_discovery.ga_top_k', 2);
        $topK = max(1, min($topK, count($fitnessMap)));
        arsort($fitnessMap, SORT_NUMERIC);
        $selected = array_slice(array_keys($fitnessMap), 0, $topK, true);
        $selected = array_values(array_map('intval', $selected));

        $nextGeneration = $selected;
        $crossoverEnabled = config('worldos.civilization_discovery.ga_crossover_enabled', false);
        if ($crossoverEnabled && count($selected) >= 2) {
            $childId = $this->crossoverAndMutate($selected[0], $selected[1]);
            if ($childId !== null) {
                $nextGeneration[] = $childId;
                Log::info('CivilizationDiscoveryService: GA crossover+mutate created child universe', ['child_id' => $childId]);
            }
        }

        return [
            'evaluated' => $fitnessMap,
            'selected' => $selected,
            'next_generation' => $nextGeneration,
        ];
    }

    /**
     * Crossover (Cách A): merge state_vector of two parents; spawn child from first parent; mutate; return child id.
     */
    private function crossoverAndMutate(int $parentAId, int $parentBId): ?int
    {
        $parentA = $this->universeRepository->find($parentAId);
        $parentB = $this->universeRepository->find($parentBId);
        if (! $parentA?->world || ! $parentB) {
            return null;
        }
        $vecA = $this->getStateVector($parentA);
        $vecB = $this->getStateVector($parentB);
        $merged = $this->mergeStateVector($vecA, $vecB);
        $mutateRate = (float) config('worldos.civilization_discovery.ga_mutate_rate', 0.05);
        $merged = $this->mutateStateVector($merged, $mutateRate, $parentA);

        $child = $this->orchestrator->spawnUniverse(
            $parentA->world,
            $parentA->id,
            $parentA->saga_id,
            ['reason' => 'ga_crossover', 'meta' => ['parent_b_id' => $parentBId]]
        );
        $this->universeRepository->update($child->id, ['state_vector' => $merged]);
        return (int) $child->id;
    }

    /**
     * Merge state vectors: zones from A, civilization (incl. discovery) from B, entropy/innovation averaged.
     *
     * @param  array<string, mixed>  $vecA
     * @param  array<string, mixed>  $vecB
     * @return array<string, mixed>
     */
    private function mergeStateVector(array $vecA, array $vecB): array
    {
        $merged = $vecA;
        $civB = $vecB['civilization'] ?? [];
        if (! empty($civB)) {
            $civA = $merged['civilization'] ?? [];
            $merged['civilization'] = array_merge($civA, $civB);
        }
        if (isset($vecB['entropy']) && isset($vecA['entropy'])) {
            $merged['entropy'] = (float) (($vecA['entropy'] + $vecB['entropy']) / 2);
        }
        if (isset($vecB['innovation']) || isset($vecA['innovation'])) {
            $merged['innovation'] = (float) ((($vecA['innovation'] ?? 0.5) + ($vecB['innovation'] ?? 0.5)) / 2);
        }
        return $merged;
    }

    /**
     * Apply small random delta to scalar fields (entropy, innovation, stability) for mutation.
     *
     * @param  array<string, mixed>  $vec
     * @return array<string, mixed>
     */
    private function mutateStateVector(array $vec, float $rate, Universe $universe): array
    {
        if ($rate <= 0) {
            return $vec;
        }
        $prng = \App\Modules\Simulation\Services\Ecology\SimulationPRNG::forUniverse($universe);
        $r = fn () => ($prng->nextFloat()) * 2 * $rate - $rate;
        if (isset($vec['entropy']) && is_numeric($vec['entropy'])) {
            $vec['entropy'] = max(0, min(1, (float) $vec['entropy'] + $r()));
        }
        if (isset($vec['innovation']) && is_numeric($vec['innovation'])) {
            $vec['innovation'] = max(0, min(1, (float) $vec['innovation'] + $r()));
        }
        if (isset($vec['stability_index']) && is_numeric($vec['stability_index'])) {
            $vec['stability_index'] = max(0, min(1, (float) $vec['stability_index'] + $r()));
        }
        $civ = $vec['civilization'] ?? [];
        if (isset($civ['discovery']['fitness']) && is_numeric($civ['discovery']['fitness'])) {
            $civ['discovery']['fitness'] = max(0, (float) $civ['discovery']['fitness'] + $r() * 10);
            $vec['civilization'] = $civ;
        }
        return $vec;
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


