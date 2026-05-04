<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;

use App\Modules\World\Models\World;
use App\Modules\Simulation\Services\Meta\MultiverseSchedulerEngine;

class WorldSimulationStatusService
{
    public function getPayload(World $world, MultiverseSchedulerEngine $scheduler): array
    {
        $universes = \App\Modules\World\Models\Universe::where('world_id', $world->id)
            ->whereIn('status', ['active', 'running', 'halted', 'restarting'])
            ->get();

        $scheduled = $scheduler->scheduleWithScores($world, 0);
        $priorityByUniverse = [];
        foreach ($scheduled as $item) {
            $uId = $item['universe']->id ?? $item->universe->id ?? null;
            if ($uId) {
                $priorityByUniverse[$uId] = [
                    'priority' => $item['priority'] ?? $item->priority ?? 0,
                    'order_index' => $item['order_index'] ?? $item->order_index ?? 0,
                ];
            }
        }

        $snapshotInterval = (int) ($world->snapshot_interval ?? config('worldos.snapshot_interval', 10));
        $tickBudget = (int) config('worldos.scheduler.tick_budget', 0);
        $priorityWeights = config('worldos.scheduler.priority_weights', [
            'novelty' => 0.25, 'complexity' => 0.30, 'civilization' => 0.25, 'entropy' => 0.20,
        ]);
        $agingFactor = (float) config('worldos.scheduler.aging_factor', 0.01);
        $forkMin = (float) config('worldos.autonomic.fork_entropy_min', 0.5);
        $archiveThresh = (float) config('worldos.autonomic.archive_entropy_threshold', 0.99);

        $tickPipelineEngines = [
            ['priority' => 1, 'name' => 'Planet Engine'],
            ['priority' => 2, 'name' => 'Climate Engine'],
            ['priority' => 3, 'name' => 'Ecology Engine'],
            ['priority' => 4, 'name' => 'Civilization Engine'],
            ['priority' => 5, 'name' => 'Politics Engine'],
            ['priority' => 6, 'name' => 'War Engine'],
            ['priority' => 7, 'name' => 'Trade Engine'],
            ['priority' => 8, 'name' => 'Knowledge Engine'],
            ['priority' => 9, 'name' => 'Culture Engine'],
            ['priority' => 10, 'name' => 'Ideology Engine'],
            ['priority' => 11, 'name' => 'Memory Engine'],
            ['priority' => 12, 'name' => 'Mythology Engine'],
            ['priority' => 13, 'name' => 'Evolution Engine'],
        ];

        $universeIds = $universes->pluck('id')->toArray();
        $latestSnapshots = \App\Modules\Simulation\Models\UniverseSnapshot::whereIn('universe_id', $universeIds)
            ->orderByDesc('tick')
            ->get()
            ->groupBy('universe_id')
            ->map(fn ($rows) => $rows->first());

        $counts = ['active' => 0, 'halted' => 0, 'restarting' => 0];
        $universesPayload = [];
        foreach ($universes as $u) {
            $status = $u->status;
            if (in_array($status, ['active', 'running'])) {
                $counts['active']++;
            } elseif ($status === 'halted') {
                $counts['halted']++;
            } elseif ($status === 'restarting') {
                $counts['restarting']++;
            }

            $snap = $latestSnapshots->get($u->id);
            $entropy = $u->entropy ?? $snap?->entropy ?? 0.5;
            $latestSnapshot = null;
            if ($snap) {
                $sv = is_array($snap->state_vector) ? $snap->state_vector : (array) json_decode($snap->state_vector ?? '{}', true);
                $latestSnapshot = [
                    'tick' => (int) $snap->tick,
                    'year' => (int) ($sv['year'] ?? $snap->tick),
                    'snapshot_interval' => $snapshotInterval,
                    'entropy' => (float) $snap->entropy,
                    'stability_index' => (float) ($snap->stability_index ?? 0.5),
                    'planet' => $sv['planet'] ?? [],
                    'civilizations' => $sv['civilizations'] ?? $sv['civilization'] ?? [],
                    'population' => $sv['population'] ?? [],
                    'economy' => $sv['economy'] ?? [],
                    'knowledge' => $sv['knowledge'] ?? [],
                    'culture' => $sv['culture'] ?? [],
                    'active_attractors' => $sv['active_attractors'] ?? [],
                    'ecology' => $sv['ecology'] ?? $sv['eco_fields'] ?? [],
                    'wars' => $sv['wars'] ?? [],
                    'alliances' => $sv['alliances'] ?? [],
                    'metrics' => is_array($snap->metrics) ? $snap->metrics : [],
                ];
            }

            $pri = $priorityByUniverse[$u->id] ?? null;
            $forkCountIfFork = $entropy >= $forkMin ? (int) floor($entropy * 5) : null;

            $universesPayload[] = [
                'id' => $u->id,
                'name' => $u->name ?? 'Universe ' . $u->id,
                'status' => $status,
                'current_tick' => (int) ($u->current_tick ?? 0),
                'current_year' => (int) ($u->current_tick ?? 0),
                'entropy' => round($entropy, 4),
                'priority' => $pri ? round($pri['priority'], 4) : null,
                'order_index' => $pri['order_index'] ?? null,
                'idle_ticks' => 0,
                'timeline_score' => null,
                'autonomic_decision' => $entropy >= $archiveThresh ? 'archive' : ($entropy >= $forkMin ? 'fork' : 'continue'),
                'fork_count_if_fork' => $forkCountIfFork,
                'latest_snapshot' => $latestSnapshot,
            ];
        }

        return [
            'world' => [
                'id' => $world->id,
                'name' => $world->name,
                'is_autonomic' => (bool) $world->is_autonomic,
                'global_tick' => (int) ($world->global_tick ?? 0),
                'snapshot_interval' => $snapshotInterval,
            ],
            'pipeline' => [
                'phase' => 'scheduler',
                'steps' => ['simulation', 'autonomic', 'scheduler', 'decision', 'cascade', 'ecology', 'timeline_selection', 'narrative'],
            ],
            'scheduler' => [
                'tick_budget' => $tickBudget,
                'priority_weights' => $priorityWeights,
                'aging_factor' => $agingFactor,
            ],
            'autonomic' => [
                'fork_entropy_min' => $forkMin,
                'archive_entropy_threshold' => $archiveThresh,
            ],
            'universes' => $universesPayload,
            'counts' => $counts,
            'tick_pipeline_engines' => $tickPipelineEngines,
        ];
    }
}


