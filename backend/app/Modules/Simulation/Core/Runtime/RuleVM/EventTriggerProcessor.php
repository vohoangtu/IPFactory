<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Core\Support\RuleEngine;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Narrative\Services\EventTriggerMapper;
use Illuminate\Support\Facades\DB;

/**
 * Data-driven event trigger processing: evaluates threshold_rules, cooldown, probability,
 * creates BranchEvent and updates event_cooldowns in state_vector.
 */
final class EventTriggerProcessor
{
    public function __construct(
        protected EventTriggerMapper $eventTriggerMapper,
        protected RuleEngine $ruleEngine,
        protected UniverseRepository $universeRepository
    ) {}

    public function process(Universe $universe, UniverseSnapshot $snapshot, SimulationRandom $rng): void
    {
        $stateVector = array_merge(
            $snapshot->state_vector ?? [],
            $universe->state_vector ?? []
        );
        $currentTick = (int) $snapshot->tick;

        $rows = DB::table('event_triggers')
            ->whereNotNull('threshold_rules')
            ->get();

        $getValue = fn (string $key) => $this->eventTriggerMapper->getMetricValue($stateVector, $key);
        $cooldowns = $stateVector['event_cooldowns'] ?? [];
        if (!is_array($cooldowns)) {
            $cooldowns = [];
        }
        $updatedCooldowns = false;

        foreach ($rows as $row) {
            $rules = $row->threshold_rules;
            if (is_string($rules)) {
                $rules = json_decode($rules, true);
            }
            if (!is_array($rules) || empty($rules)) {
                continue;
            }

            if (!$this->ruleEngine->evaluate($rules, $stateVector, $getValue)) {
                continue;
            }

            $eventType = $row->event_type;
            $lastFired = isset($cooldowns[$eventType]) ? (int) $cooldowns[$eventType] : 0;
            if ($currentTick - $lastFired < (int)($row->cooldown_ticks ?? 10)) {
                continue;
            }

            if ($rng->float(0, 1) > (float)($row->probability ?? 0.2)) {
                continue;
            }

            BranchEvent::create([
                'universe_id' => $universe->id,
                'from_tick' => $currentTick,
                'event_type' => $eventType,
                'payload' => [
                    'trigger_id' => $row->id,
                    'context' => [
                        'entropy' => $stateVector['entropy'] ?? null,
                        'stability_index' => $stateVector['stability_index'] ?? null,
                    ],
                ],
            ]);

            $cooldowns[$eventType] = $currentTick;
            $updatedCooldowns = true;
        }

        if ($updatedCooldowns) {
            $vec = $universe->state_vector ?? [];
            $vec['event_cooldowns'] = $cooldowns;
            $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
        }
    }
}

