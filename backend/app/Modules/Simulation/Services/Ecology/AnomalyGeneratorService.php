<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function array_merge;
use function min;
use function max;
use function count;
use function is_array;
use function round;
use function array_slice;

/**
 * AnomalyGeneratorService: Triggers unclassifiable anomalies (§V25).
 */
class AnomalyGeneratorService
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $this->ruleVm->evaluateAndApplyWithDsl($state, 'simulation/anomalies', $tick);
        Log::debug("AnomalyGeneratorService: Universe {$state->get('universe_id')} anomalies evaluated at tick {$tick}");
    }

    /** Natural disaster types (Doc §14 Disaster struct). */
    public const DISASTER_DROUGHT = 'drought';
    public const DISASTER_FLOOD = 'flood';
    public const DISASTER_QUAKE = 'earthquake';
    public const DISASTER_STORM = 'storm';
    public const DISASTER_PESTILENCE = 'pestilence';

    public const DISASTER_TYPES = [
        self::DISASTER_DROUGHT,
        self::DISASTER_FLOOD,
        self::DISASTER_QUAKE,
        self::DISASTER_STORM,
        self::DISASTER_PESTILENCE,
    ];

    /**
     * Evaluate anomaly rules and spawn if triggered.
     */
    public function spawnAnomaly(Universe $universe): void
    {
        $dslFile = resource_path('worldos_rules/simulation/anomalies.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        // Prepare state - include random chance for DSL to use
        $state = array_merge($universe->state_vector ?? [], [
            'tick' => (int) $universe->current_tick,
            'random_chance' => (float) (lcg_value()), // 0..1
            'has_zones' => isset($universe->state_vector['zones']) && !empty($universe->state_vector['zones']),
            'instability_gradient' => (float) ($universe->state_vector['instability_gradient'] ?? 0.0),
        ]);

        $result = $this->ruleVm->evaluateRawState($state, $dsl);

        if (!($result['ok'] ?? false)) {
            return;
        }

        $outputs = $result['outputs'] ?? [];
        foreach ($outputs as $out) {
            if (($out['type'] ?? '') === 'event' && ($out['event_name'] ?? '') === 'SPAWN_ANOMALY') {
                $metadata = $out['metadata'] ?? [];
                $this->executeAnomalySpawn(
                    $universe, 
                    $metadata['anomaly_type'] ?? 'unknown',
                    (float) ($metadata['severity'] ?? 0.5),
                    $metadata['description'] ?? 'Dị thường không xác định.',
                    $metadata
                );
            }
        }
    }

    /**
     * Internal execution of anomaly effects (Driven by DSL metadata).
     */
    protected function executeAnomalySpawn(Universe $universe, string $type, float $severity, string $description, array $metadata = []): void
    {
        $details = ['description' => $description];
        $vec = $universe->state_vector ?? [];
        $prng = \App\Support\Simulation\SimulationPRNG::forUniverse($universe);

        switch ($type) {
            case 'biological_hivemind':
                if (isset($vec['zones']) && is_array($vec['zones']) && count($vec['zones']) > 0) {
                    $zoneIdx = $prng->arrayRand($vec['zones']);
                    $zone = $vec['zones'][$zoneIdx] ?? null;

                    if (is_array($zone)) {
                        $stressInc = (float) ($metadata['material_stress_inc'] ?? $severity);
                        $agentOrder = (float) ($metadata['agent_order'] ?? 1.0);

                        $zone['material_stress'] = min(1.0, ($zone['material_stress'] ?? 0) + $stressInc);
                        
                        if (isset($zone['state']['agents']) && is_array($zone['state']['agents'])) {
                            foreach ($zone['state']['agents'] as &$agent) {
                                if (is_array($agent)) {
                                    $agent['order'] = $agentOrder;
                                }
                            }
                        }
                        
                        $vec['zones'][$zoneIdx] = $zone;
                        $details['zone_id'] = $zoneIdx;
                    }
                }
                break;
            case 'spatial_fracture':
                $scars = $vec['scars'] ?? [];
                $intensity = (float) ($metadata['scar_intensity'] ?? 0.99);
                $coherenceInc = (float) ($metadata['coherence_inc'] ?? 0.2);

                $scars[] = [
                    'type' => 'spatial_fracture',
                    'tick' => $universe->current_tick,
                    'description' => $description,
                    'intensity' => $intensity
                ];
                $vec['scars'] = $scars;
                $universe->structural_coherence = min(1.0, $universe->structural_coherence + $coherenceInc);
                break;
            case 'axiom_duplication':
                if (isset($vec['axioms'])) {
                    $worldAxioms = $universe->world?->axiom ?? [];
                    if (!empty($worldAxioms)) {
                        $duplicate = $prng->randomElement($worldAxioms);
                        $vec['axioms'][] = $duplicate;
                        $details['duplicated_axiom'] = $duplicate;
                    }
                }
                break;
        }

        $universe->state_vector = $vec;
        $universe->save();

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'to_tick' => $universe->current_tick,
            'type' => 'chaos_anomaly',
            'raw_payload' => ['anomaly_type' => $type, 'details' => $details],
        ]);
    }

    /**
     * Evaluate disaster rules and spawn if triggered.
     */
    public function spawnNaturalDisaster(Universe $universe, array $overrides = []): void
    {
        $dslFile = \resource_path('worldos_rules/simulation/anomalies.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        // For disasters, we usually evaluate per zone or globally. 
        // Here we'll process the decision logic in DSL.
        $state = $universe->state_vector ?? [];
        $state['random_chance'] = lcg_value();
        $state['random_float_0_6'] = lcg_value() * 0.6;

        $result = $this->ruleVm->evaluateRawState($state, $dsl);
        $outputs = $result['outputs'] ?? [];

        foreach ($outputs as $out) {
            if (($out['type'] ?? '') === 'event' && ($out['event_name'] ?? '') === 'NATURAL_DISASTER_TRIGGERED') {
                $metadata = $out['metadata'] ?? [];
                $intensity = (float) ($metadata['intensity'] ?? 0.5);
                $this->executeDisaster($universe, $intensity, $metadata['description'] ?? 'Thiên tai giáng xuống.', $metadata);
            }
        }
    }

    protected function executeDisaster(Universe $universe, float $intensity, string $description, array $metadata = []): void
    {
        $prng = \App\Support\Simulation\SimulationPRNG::forUniverse($universe);
        $type = self::DISASTER_TYPES[$prng->arrayRand(self::DISASTER_TYPES)];
        $tick = $universe->current_tick ?? 0;
        $limit = (int) ($metadata['disaster_limit'] ?? 20);

        $disaster = [
            'type' => $type,
            'zone_id' => null, // Simplified
            'intensity' => round($intensity, 2),
            'tick' => $tick,
            'description' => $description
        ];

        $vec = is_array($universe->state_vector) ? $universe->state_vector : [];
        $disasters = $vec['disasters'] ?? [];
        $disasters[] = $disaster;
        $vec['disasters'] = array_slice($disasters, -$limit);
        $universe->state_vector = $vec;
        $universe->save();

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'natural_disaster',
            'raw_payload' => ['disaster' => $disaster],
        ]);
    }
}





