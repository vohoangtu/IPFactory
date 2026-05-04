<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\InstitutionalEntity as InstitutionalModel;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Models\Chronicle;
use function resource_path;
use function config;
use function event;
use function app;
use function file_get_contents;
use function array_merge;
use function str_replace;
use function strtoupper;
use function count;
use function rand;
use function usort;
use function array_column;
use function array_slice;
use function shuffle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use Illuminate\Support\Facades\DB;

/**
 * Great Filter Engine: Monitors global stability and triggers systemic crises.
 * Based on WORLDOS_V6 macro-evolutionary specs.
 */
class GreatFilterEngine
{
    const CRISIS_SINGULARITY = 'singularity_collapse';
    const CRISIS_STAGNATION = 'institutional_stagnation';
    const CRISIS_VOID_BREACH = 'void_breach';
    const CRISIS_RESOURCE_WAR = 'total_resource_war';

    public function __construct(
        protected \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm
    ) {}

    /**
     * Process global state to detect and handle Great Filter events.
     * When $rng is provided, randomness is deterministic (replayable).
     */
    public function process(Universe $universe, int $tick, array $stateVector, ?SimulationRandom $rng = null): array
    {
        $snapshot = $universe->snapshots()->where('tick', $tick)->first();
        if (!$snapshot) return [];

        // Load Great Filter DSL
        $dsl = @file_get_contents(\resource_path('worldos_rules/simulation/great_filter.dsl')) ?: '';
        
        // Prepare extra state for DSL (avg_capacity, trust)
        $avgCapacity = InstitutionalModel::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->avg('org_capacity') ?? 10.0;
        
        $trust = $this->calculateAverageTrust($universe);
        
        // Cần truyền thêm các biến này vào Rule VM. 
        // Hiện tại RuleVmService chưa hỗ trợ custom extra state dễ dàng, 
        // ta có thể tạm thời đính kèm vào state vector của snapshot trong bộ nhớ.
        $snapshot->state_vector = array_merge($snapshot->state_vector ?? [], [
            'civilization' => [
                'politics' => [
                    'avg_capacity' => $avgCapacity,
                    'trust' => $trust
                ]
            ]
        ]);

        $result = $this->ruleVm->evaluateRaw($snapshot->state_vector, $dsl);
        
        if (!($result['ok'] ?? false)) return [];

        $outputs = $result['outputs'] ?? [];
        $crises = [];

        foreach ($outputs as $out) {
            if ($out['type'] === 'event') {
                $type = match($out['event_name']) {
                    'CRISIS_SINGULARITY_TRIGGERED' => self::CRISIS_SINGULARITY,
                    'CRISIS_STAGNATION_TRIGGERED' => self::CRISIS_STAGNATION,
                    'CRISIS_VOID_BREACH_TRIGGERED' => self::CRISIS_VOID_BREACH,
                    default => null
                };
                
                if ($type) {
                    $crises[] = $this->triggerCrisis($universe, $tick, $type, $rng, $out['metadata'] ?? []);
                }
            }
        }

        return $crises;
    }

    protected function triggerCrisis(Universe $universe, int $tick, string $type, ?SimulationRandom $rng = null, array $metadata = []): array
    {
        $vec = $universe->state_vector ?? [];
        if (isset($vec['active_crises'][$type])) {
            return ['type' => $type, 'status' => 'active'];
        }

        $content = $metadata['description'] ?? "BỘ LỌC VĨ ĐẠI: Một thử thách vĩ mô đang đe dọa sự tồn vong của toàn bộ vũ trụ.";

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'great_filter_event',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "CẢNH BÁO BỘ LỌC VĨ ĐẠI: {$content}"
            ],
        ]);

        // Broadcast as Anomaly
        \event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
            'title' => "BỘ LỌC VĨ ĐẠI: " . strtoupper(str_replace('_', ' ', $type)),
            'description' => $content,
            'severity' => 'CRITICAL'
        ]));

        // Apply immediate effects
        $this->applyCrisisEffects($universe, $type, $tick, $rng, $metadata);

        // Record in state_vector
        $vec = $universe->fresh()->state_vector; // Get fresh state
        $activeCrises = $vec['active_crises'] ?? [];
        $activeCrises[$type] = [
            'started_at' => $tick,
            'intensity' => (float) ($metadata['intensity'] ?? 1.0)
        ];
        $vec['active_crises'] = $activeCrises;
        $universe->update(['state_vector' => $vec]);

        return ['type' => $type, 'status' => 'triggered'];
    }

    protected function applyCrisisEffects(Universe $universe, string $type, int $tick, ?SimulationRandom $rng = null, array $metadata = []): void
    {
        switch ($type) {
            case self::CRISIS_SINGULARITY:
                $killPct = (float) ($metadata['actor_kill_pct'] ?? 0.3);
                $capPenalty = (float) ($metadata['capacity_penalty'] ?? 0.2);
                
                $count = Actor::where('universe_id', $universe->id)->where('is_alive', true)->count();
                Actor::where('universe_id', $universe->id)
                    ->where('is_alive', true)
                    ->inRandomOrder()
                    ->limit((int)($count * $killPct))
                    ->update(['is_alive' => false]);
                
                InstitutionalModel::where('universe_id', $universe->id)
                    ->where('entity_type', 'CIVILIZATION')
                    ->whereNull('collapsed_at_tick')
                    ->update([
                        'org_capacity' => DB::raw("GREATEST(0.1, org_capacity - {$capPenalty})"),
                        'legitimacy' => DB::raw('GREATEST(0.0, legitimacy - 0.15)')
                    ]);
                break;

            case self::CRISIS_STAGNATION:
                $capMult = (float) ($metadata['capacity_multiplier'] ?? 0.4);
                $memMult = (float) ($metadata['memory_multiplier'] ?? 0.6);
                
                InstitutionalModel::where('universe_id', $universe->id)
                    ->whereNull('collapsed_at_tick')
                    ->update([
                        'org_capacity' => DB::raw("org_capacity * {$capMult}"),
                        'institutional_memory' => DB::raw("institutional_memory * {$memMult}"),
                        'legitimacy' => DB::raw('GREATEST(0.0, legitimacy - 0.3)')
                    ]);
                break;

            case self::CRISIS_VOID_BREACH:
                $traumaBoost = (float) ($metadata['trauma_boost'] ?? 0.6);
                $fragChance = (float) ($metadata['fragmentation_chance'] ?? 0.5);
                
                $vec = $universe->state_vector;
                $vec['trauma'] = ($vec['trauma'] ?? 0) + $traumaBoost;
                $universe->update(['state_vector' => $vec]);

                $civs = InstitutionalModel::where('universe_id', $universe->id)
                    ->where('entity_type', 'CIVILIZATION')
                    ->whereNull('collapsed_at_tick')
                    ->get();

                foreach ($civs as $civ) {
                    $map = $civ->influence_map ?? [];
                    if (count($map) > 1 && (rand(0, 100) / 100 < $fragChance)) {
                        $pct = $rng ? $rng->int(20, 40) : rand(20, 40);
                        $removeCount = (int)(count($map) * $pct / 100);
                        if ($rng) {
                            $withKeys = [];
                            foreach ($map as $i => $v) {
                                $withKeys[] = ['v' => $v, 'r' => $rng->nextFloat()];
                            }
                            usort($withKeys, fn ($a, $b) => $a['r'] <=> $b['r']);
                            $remaining = array_slice(array_column($withKeys, 'v'), $removeCount);
                        } else {
                            shuffle($map);
                            $remaining = array_slice($map, $removeCount);
                        }
                        $civ->update(['influence_map' => $remaining]);
                    }
                }
                break;
        }
    }

    protected function calculateAverageTrust(Universe $universe): float
    {
        $actors = Actor::where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->select('traits')
            ->get();

        if ($actors->isEmpty()) {
            return 0.5;
        }

        $totalTrust = 0.0;
        foreach ($actors as $actor) {
            // Trust is mapped to Pragmatism (index 7 of 17D vector)
            $totalTrust += (float) ($actor->traits[7] ?? 0.5);
        }

        return $totalTrust / $actors->count();
    }
}




