<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;
use Illuminate\Support\Facades\Http;

/**
 * HTTP bridge to WorldOS simulation engine (Rust).
 * Engine must expose POST /advance with JSON body and response.
 * Set SIMULATION_ENGINE_GRPC_URL to http://localhost:50052 (or http://host:port).
 * Serialization: JSON only. Phase 1 (optional binary) deferred.
 */
class HttpSimulationEngineClient implements SimulationEngineClientInterface
{
    public function __construct(
        protected string $baseUrl
    ) {}

    public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
    {
        $url = rtrim($this->baseUrl, '/').'/advance';
        $payload = [
            'universe_id' => $universeId,
            'ticks' => $ticks,
            'state_input' => $stateInput,
            'world_config' => $worldConfig,
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'snapshot' => null,
                'error_message' => $e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'snapshot' => null,
                'error_message' => $response->body() ?: 'HTTP '.$response->status(),
            ];
        }

        $data = $response->json();
        $ok = $data['ok'] ?? false;
        $errorMessage = $data['error_message'] ?? '';
        $snapshotData = $data['snapshot'] ?? null;

        $snapshot = null;
        if ($snapshotData && is_array($snapshotData)) {
            $snapshot = [
                'universe_id' => $snapshotData['universe_id'] ?? $universeId,
                'tick' => $snapshotData['tick'] ?? $ticks,
                'state_vector' => $snapshotData['state_vector'] ?? '{}',
                'entropy' => $snapshotData['entropy'] ?? null,
                'stability_index' => $snapshotData['stability_index'] ?? null,
                'metrics' => $snapshotData['metrics'] ?? '{}',
                'sci' => $snapshotData['sci'] ?? null,
                'instability_gradient' => $snapshotData['instability_gradient'] ?? null,
                'global_fields' => $snapshotData['global_fields'] ?? null,
            ];
        }

        return [
            'ok' => $ok,
            'snapshot' => $snapshot,
            'error_message' => $errorMessage,
        ];
    }

    public function merge(string $stateA, string $stateB): array
    {
        $url = rtrim($this->baseUrl, '/').'/merge';
        $payload = [
            'state_a' => $stateA,
            'state_b' => $stateB,
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'snapshot' => null, 'error_message' => $e->getMessage()];
        }

        if (!$response->successful()) {
            return ['ok' => false, 'snapshot' => null, 'error_message' => $response->body() ?: 'HTTP '.$response->status()];
        }

        $data = $response->json();
        $snapshotData = $data['snapshot'] ?? null;
        $snapshot = null;

        if ($snapshotData && is_array($snapshotData)) {
            $snapshot = [
                'universe_id' => 0,
                'tick' => $snapshotData['tick'] ?? 0,
                'state_vector' => $snapshotData['state_vector'] ?? '{}',
                'entropy' => $snapshotData['entropy'] ?? null,
                'stability_index' => $snapshotData['stability_index'] ?? null,
                'metrics' => $snapshotData['metrics'] ?? '{}',
                'sci' => $snapshotData['sci'] ?? null,
                'instability_gradient' => $snapshotData['instability_gradient'] ?? null,
                'global_fields' => $snapshotData['global_fields'] ?? null,
            ];
        }

        return [
            'ok' => $data['ok'] ?? false,
            'snapshot' => $snapshot,
            'error_message' => $data['error_message'] ?? '',
        ];
    }

    public function batchAdvance(array $requests): array
    {
        $url = rtrim($this->baseUrl, '/').'/batch-advance';
        $payload = ['requests' => $requests];

        try {
            $response = Http::timeout(120)->post($url, $payload);
        } catch (\Throwable $e) {
            return ['responses' => [], 'error_message' => $e->getMessage()];
        }

        if (!$response->successful()) {
            return ['responses' => [], 'error_message' => $response->body() ?: 'HTTP '.$response->status()];
        }

        $data = $response->json();
        $responses = [];

        foreach (($data['responses'] ?? []) as $res) {
            $snapshotData = $res['snapshot'] ?? null;
            $snapshot = null;
            if ($snapshotData && is_array($snapshotData)) {
                $snapshot = [
                    'universe_id' => $snapshotData['universe_id'] ?? 0,
                    'tick' => $snapshotData['tick'] ?? 0,
                    'state_vector' => $snapshotData['state_vector'] ?? '{}',
                    'entropy' => $snapshotData['entropy'] ?? null,
                    'stability_index' => $snapshotData['stability_index'] ?? null,
                    'metrics' => $snapshotData['metrics'] ?? '{}',
                    'sci' => $snapshotData['sci'] ?? null,
                    'instability_gradient' => $snapshotData['instability_gradient'] ?? null,
                    'global_fields' => $snapshotData['global_fields'] ?? null,
                ];
            }
            $responses[] = [
                'ok' => $res['ok'] ?? false,
                'snapshot' => $snapshot,
                'error_message' => $res['error_message'] ?? '',
            ];
        }

        return ['responses' => $responses];
    }

    public function analyzeTrajectory(array $points, float $threshold = 0.1): array
    {
        $url = rtrim($this->baseUrl, '/').'/analyze-trajectory';
        $payload = [
            'points' => $points,
            'recurrence_threshold' => $threshold,
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error_message' => $e->getMessage()];
        }

        if (!$response->successful()) {
            return ['ok' => false, 'error_message' => $response->body() ?: 'HTTP '.$response->status()];
        }

        return $response->json();
    }

    public function evaluateRules(array $state, ?string $rulesDsl = null): array
    {
        $url = rtrim($this->baseUrl, '/').'/evaluate-rules';
        $payload = [
            'state' => $state,
            'rules_dsl' => $rulesDsl,
        ];

        try {
            $response = Http::timeout(15)->post($url, $payload);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'outputs' => [],
                'error_message' => $e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'outputs' => [],
                'error_message' => $response->body() ?: 'HTTP '.$response->status(),
            ];
        }

        $data = $response->json();
        return [
            'ok' => $data['ok'] ?? false,
            'outputs' => $data['outputs'] ?? [],
            'error_message' => $data['error_message'] ?? null,
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
        $url = rtrim($this->baseUrl, '/').'/process-actors-soa';
        $payload = [
            'tick'               => $tick,
            'ids'                => $ids,
            'zone_ids'           => $zoneIds,
            'hunger'             => $hunger,
            'energy'             => $energy,
            'fear'               => $fear,
            'trauma'             => $trauma,
            'heroic_types'       => $heroicTypes,
            'lineage_ids'        => $lineageIds,
            'memes'              => $memes,
            'traits_matrix'      => $traitsMatrix,
            'behavior_states'    => $behaviorStates,
            'behavior_graphs'    => $behaviorGraphs,
            'archetypes'         => $archetypes,
            'social_graph'       => $socialGraph,
            'edicts'             => $edicts,
            'faction_ids'        => $factionIds,
            'faction_loyalty'    => $factionLoyalty,
            'is_observed'        => $isObserved,
            'narrative_context'  => $narrativeContext,
            'faction_relations'  => $factionRelations,
            'belief_definitions' => $beliefDefinitions,
            'belief_alignments'  => $beliefAlignments,
            'tech_definitions'   => $techDefinitions,
            'actor_tech_levels'  => $actorTechLevels,
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);
            return $response->json() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function processFieldsV7(array $fields, array $neighborCounts, array $neighborOffsets, array $neighbors, float $diffusionRate, float $preservationRate): array
    {
        $url = rtrim($this->baseUrl, '/').'/process-fields';
        $payload = ['fields' => $fields, 'neighbor_counts' => $neighborCounts, 'neighbor_offsets' => $neighborOffsets, 'neighbors' => $neighbors, 'diffusion_rate' => $diffusionRate, 'preservation_rate' => $preservationRate];
        try {
            $response = Http::timeout(30)->post($url, $payload);
            return $response->json() ?: $fields;
        } catch (\Throwable $e) {
            return $fields;
        }
    }

    public function computeMetabolismGrid(array $populations, array $biomasses, array $industries, float $efficiency, float $baseEnergy): array
    {
        $url = rtrim($this->baseUrl, '/').'/compute-metabolism';
        $payload = compact('populations', 'biomasses', 'industries', 'efficiency', 'baseEnergy');
        try {
            $response = Http::timeout(30)->post($url, $payload);
            return $response->json() ?: ['total_waste' => 0.0, 'net_energies' => []];
        } catch (\Throwable $e) {
            return ['total_waste' => 0.0, 'net_energies' => []];
        }
    }

    public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float
    {
        $url = rtrim($this->baseUrl, '/').'/calculate-vocation-alignment';
        $payload = compact('actorMotivation', 'targetProfile');
        try {
            $response = Http::timeout(15)->post($url, $payload);
            return (float) ($response->json()['alignment'] ?? 0.0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function getCombinedGravity(array $rulesets): float
    {
        $url = rtrim($this->baseUrl, '/').'/get-combined-gravity';
        $payload = compact('rulesets');
        try {
            $response = Http::timeout(15)->post($url, $payload);
            return (float) ($response->json()['gravity'] ?? 1.0);
        } catch (\Throwable $e) {
            return 1.0;
        }
    }
}
