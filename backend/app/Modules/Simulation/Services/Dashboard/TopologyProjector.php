<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Dashboard;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

class TopologyProjector
{
    public function project(WorldState $state, int $universeId): array
    {
        $zones = $state->getZones();
        $neighbors = $state->getNeighboringRealities();

        $nodes = [];
        $edges = [];

        foreach ($zones as $idx => $zone) {
            $nodes[] = [
                'id' => "zone_{$idx}",
                'type' => 'zone',
                'label' => $zone['name'] ?? "Vùng {$idx}",
                'metrics' => [
                    'population' => $zone['state']['population_proxy'] ?? 0.5,
                    'stability' => $zone['state']['stability'] ?? 1.0,
                ],
            ];
        }

        foreach ($neighbors as $n) {
            $nodes[] = [
                'id' => "uni_{$n['id']}",
                'type' => 'universe',
                'label' => $n['name'],
                'metrics' => [
                    'entropy' => $n['entropy'] ?? 0.5,
                    'similarity' => $n['similarity'] ?? 1.0,
                ],
            ];

            $edges[] = [
                'id' => "e_trade_{$universeId}_{$n['id']}",
                'source' => "uni_{$universeId}",
                'target' => "uni_{$n['id']}",
                'type' => 'quantum_trade',
                'label' => 'Quantum Trade Route',
                'intensity' => $n['similarity'] ?? 0.8,
            ];
        }

        return [
            'universe_id' => $universeId,
            'tick' => (int) $state->get('tick'),
            'topology' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }
}
