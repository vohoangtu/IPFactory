<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Phase 1 stub: returns empty snapshot without calling Rust engine.
 * Replace with gRPC client in Phase 3.
 */
class StubSimulationEngineClient implements SimulationEngineClientInterface
{
    public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
    {
        // Simple mock of a simulation advance
        $state = $stateInput;
        $currentTick = $state['tick'] ?? 0;
        $entropy = $state['global_entropy'] ?? $state['entropy'] ?? 0.1;
        $stability = $state['stability_index'] ?? 1.0;
        
        return [
            'ok' => true,
            'snapshot' => [
                'universe_id' => $universeId,
                'tick' => $currentTick + $ticks,
                'state_vector' => $state,
                'entropy' => $entropy,
                'stability_index' => $stability,
                'metrics' => $state['metrics'] ?? [],
            ],
            'error_message' => '',
        ];
    }

    public function merge(string $stateA, string $stateB): array
    {
        return [
            'ok' => true,
            'snapshot' => [
                'universe_id' => 0,
                'tick' => 0,
                'state_vector' => '{}',
                'entropy' => 0.5,
                'stability_index' => 0.5,
                'metrics' => '{}',
            ],
            'error_message' => '',
        ];
    }

    public function batchAdvance(array $requests): array
    {
        $responses = [];
        foreach ($requests as $req) {
            $responses[] = $this->advance($req['universe_id'], $req['ticks'], $req['state_input'] ?? [], $req['world_config'] ?? null);
        }
        return ['responses' => $responses];
    }

    public function analyzeTrajectory(array $points, float $threshold = 0.1): array
    {
        return [
            'is_strange_attractor' => false,
            'is_bounded' => true,
            'recurrence_rate' => 0.05,
            'max_lyapunov_estimate' => -0.1,
            'trajectory_variance' => 0.1,
            'basin_center' => [],
            'basin_radius' => 0.0,
            'regime_transitions' => [],
        ];
    }

    public function evaluateRules(array $state, ?string $rulesDsl = null): array
    {
        $outputs = [];

        // Mock a calculation result if it looks like a skill execution
        if ($rulesDsl && (str_contains($rulesDsl, 'calc') || str_contains($rulesDsl, 'damage'))) {
            $outputs[] = [
                'type' => 'Calc',
                'path' => 'execution_result.value',
                'value' => 150.5 // Mock damage value
            ];
            $outputs[] = [
                'type' => 'SetPath',
                'path' => 'execution_result.status',
                'value' => 'success'
            ];
        }

        return [
            'ok' => true,
            'outputs' => $outputs,
            'error_message' => null,
        ];
    }

    public function processActorsSoa(
        int $tick,
        array $ids,
        array $zoneIds,
        array $hunger,
        array $energy,
        array $fear,
        array $trauma,
        array $heroicTypes,
        array $lineageIds,
        array $memes,
        array $traitsMatrix,
        array $behaviorStates = [],
        array $behaviorGraphs = [],
        array $archetypes = [],
        array $socialGraph = [],
        array $edicts = [],
        array $factionIds = [],
        array $factionLoyalty = [],
        bool $isObserved = false,
        array $narrativeContext = [],
        array $factionRelations = [],
        array $beliefDefinitions = [],
        array $beliefAlignments = [],
        array $techDefinitions = [],
        array $actorTechLevels = []
    ): array {
        return array_fill(0, count($ids), ['action_id' => 0, 'new_hunger' => 0.5, 'new_energy' => 0.5, 'new_trauma' => 0.0]);
    }

    public function processFieldsV7(array $fields, array $neighborCounts, array $neighborOffsets, array $neighbors, float $diffusionRate, float $preservationRate): array
    {
        return $fields;
    }

    public function computeMetabolismGrid(array $populations, array $biomasses, array $industries, float $efficiency, float $baseEnergy): array
    {
        return ['total_waste' => 0.0, 'net_energies' => array_fill(0, count($populations), 0.0)];
    }

    public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float
    {
        return 0.5;
    }

    public function getCombinedGravity(array $rulesets): float
    {
        return 1.0;
    }
}
