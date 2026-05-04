<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Core\Support\RuleEngine;
use App\Modules\Narrative\Services\EventTriggerMapper;
use Illuminate\Support\Facades\DB;

/**
 * Evaluates civilization attractors from activation_rules; writes active_attractors to state_vector.
 * EventTriggerProcessor uses active_attractors to modulate event probabilities via force_map.
 * Doc §20: pattern library + score_patterns for emergence detection.
 */
final class AttractorEngine
{
    /** Pattern library for emergence (Doc §20): pattern_id => label. */
    public const PATTERN_INDUSTRIALIZATION = 'industrialization';
    public const PATTERN_REVOLUTION = 'revolution';
    public const PATTERN_COLLAPSE = 'collapse';
    public const PATTERN_FORMATION = 'formation';

    private const PATTERN_LIBRARY = [
        self::PATTERN_INDUSTRIALIZATION => 'Industrialization',
        self::PATTERN_REVOLUTION => 'Revolution',
        self::PATTERN_COLLAPSE => 'Collapse',
        self::PATTERN_FORMATION => 'Formation',
    ];
    public function __construct(
        protected RuleEngine $ruleEngine,
        protected EventTriggerMapper $eventTriggerMapper,
        protected UniverseRepository $universeRepository
    ) {}

    /**
     * Evaluate which attractors are active for this universe/snapshot; persist to universe.state_vector.
     */
    public function evaluate(Universe $universe, UniverseSnapshot $snapshot): array
    {
        $stateVector = array_merge(
            $snapshot->state_vector ?? [],
            $universe->state_vector ?? []
        );

        $rows = DB::table('civilization_attractors')->get();
        $getValue = fn (string $key) => $this->eventTriggerMapper->getMetricValue($stateVector, $key);
        $active = [];

        $confidenceThreshold = (float) config('worldos.emergence.confidence_threshold', 0.7);
        $emergenceEvents = [];

        foreach ($rows as $row) {
            $rules = $row->activation_rules;
            if (is_string($rules)) {
                $rules = json_decode($rules, true);
            }
            if (!is_array($rules) || empty($rules)) {
                continue;
            }
            if (!$this->ruleEngine->evaluate($rules, $stateVector, $getValue)) {
                continue;
            }
            $forceMap = $row->force_map;
            if (is_string($forceMap)) {
                $forceMap = json_decode($forceMap, true);
            }
            $score = 1.0;
            $active[] = [
                'type' => $row->name,
                'strength' => $score,
                'force_map' => is_array($forceMap) ? $forceMap : [],
                'confidence' => $score,
            ];
            if ($score >= $confidenceThreshold) {
                $emergenceEvents[] = ['pattern' => $row->name, 'confidence' => $score, 'tick' => $snapshot->tick];
            }
        }

        if (!empty($active)) {
            $vec = $universe->state_vector ?? [];
            if (!is_array($vec)) {
                $vec = [];
            }
            $vec['active_attractors'] = $active;
            if (!empty($emergenceEvents)) {
                $vec['emergence_events'] = array_slice(array_merge($vec['emergence_events'] ?? [], $emergenceEvents), -50);
            }
            $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
        }

        return ['attractors' => $active, 'emergence_events' => $emergenceEvents];
    }

    /**
     * Score patterns against snapshot (Doc §20). Returns list of { pattern_id, score } above confidence_threshold.
     *
     * @param  array<string, mixed>  $snapshot  state_vector + metrics
     * @return array<int, array{pattern_id: string, score: float}>
     */
    public function scorePatterns(array $snapshot): array
    {
        $getValue = fn (string $key) => $this->eventTriggerMapper->getMetricValue($snapshot, $key);
        $threshold = (float) config('worldos.emergence.confidence_threshold', 0.7);
        $out = [];
        $rows = DB::table('civilization_attractors')->get();
        foreach ($rows as $row) {
            $rules = $row->activation_rules;
            if (is_string($rules)) {
                $rules = json_decode($rules, true);
            }
            if (! is_array($rules) || empty($rules)) {
                continue;
            }
            if (! $this->ruleEngine->evaluate($rules, $snapshot, $getValue)) {
                continue;
            }
            $score = 1.0;
            if ($score >= $threshold) {
                $out[] = ['pattern_id' => $row->name, 'score' => $score];
            }
        }
        return $out;
    }

    public static function getPatternLibrary(): array
    {
        return self::PATTERN_LIBRARY;
    }
}

