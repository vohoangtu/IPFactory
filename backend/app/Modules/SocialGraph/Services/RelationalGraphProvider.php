<?php

namespace App\Modules\SocialGraph\Services;

use App\Contracts\GraphProviderInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class RelationalGraphProvider implements GraphProviderInterface
{
    public function getUniverseNodes(int $universeId): array
    {
        $nodes = [];
        $universe = Universe::find($universeId);
        if (!$universe) return [];

        // 1. Universe Node
        $nodes[] = [
            'id' => "universe_{$universeId}",
            'type' => 'Universe',
            'label' => $universe->name ?? "Universe #{$universeId}",
            'data' => [
                'current_tick' => $universe->current_tick,
                'status' => $universe->status,
                'level' => $universe->level,
            ]
        ];

        // 2. Snapshots as Nodes (limit last 20 for performance)
        $snapshots = UniverseSnapshot::where('universe_id', $universeId)
            ->orderBy('tick', 'desc')
            ->limit(20)
            ->get();

        foreach ($snapshots as $s) {
            $nodes[] = [
                'id' => "snapshot_{$s->id}",
                'type' => 'Snapshot',
                'label' => "Tick {$s->tick}",
                'data' => [
                    'entropy' => $s->entropy,
                    'stability' => $s->stability_index,
                    'material_stress' => ($s->metrics ?? [])['material_stress'] ?? 0,
                ]
            ];
        }

        // 3. Myth Scars as Nodes
        $scars = \App\Modules\Narrative\Models\MythScar::where('universe_id', $universeId)->get();
        foreach ($scars as $scar) {
            $nodes[] = [
                'id' => "scar_{$scar->id}",
                'type' => 'MythScar',
                'label' => $scar->name,
                'data' => [
                    'severity' => $scar->severity,
                    'zone_id' => $scar->zone_id,
                ]
            ];
        }

        // 4. Ecology Nodes (Zones)
        $sv = is_string($universe->state_vector) ? json_decode($universe->state_vector, true) : ($universe->state_vector ?? []);
        $zones = $sv['zones'] ?? [];
        foreach ($zones as $idx => $zone) {
            $state = $zone['state'] ?? $zone;
            $nodes[] = [
                'id' => "ecology_zone_{$universeId}_{$idx}",
                'type' => 'Ecology',
                'label' => "Zone {$idx}: " . ($state['ecosystem_state'] ?? 'Stable'),
                'data' => [
                    'temperature' => $state['temperature'] ?? null,
                    'biome' => $state['ecosystem_state'] ?? null,
                    'ice' => $state['ice_coverage'] ?? 0,
                ]
            ];
        }

        // 5. Actors (Heroic or High Influence ones first)
        $actors = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universeId)
            ->where('is_alive', true)
            ->orderByDesc('metrics->influence')
            ->limit(40)
            ->get();

        foreach ($actors as $actor) {
            $nodes[] = [
                'id' => "actor_{$actor->id}",
                'type' => 'Actor',
                'label' => $actor->name,
                'data' => [
                    'id' => $actor->id,
                    'archetype' => $actor->archetype,
                    'is_heroic' => $actor->is_heroic ?? false,
                    'influence' => $actor->metrics['influence'] ?? 0,
                ]
            ];
        }

        return $nodes;
    }

    public function getUniverseEdges(int $universeId): array
    {
        $edges = [];
        
        // 1. Snapshot Flow (Chronological Edges)
        $snapshots = UniverseSnapshot::where('universe_id', $universeId)
            ->orderBy('tick', 'desc')
            ->limit(20)
            ->get()
            ->reverse();

        $prevId = null;
        foreach ($snapshots as $s) {
            $currentId = "snapshot_{$s->id}";
            if ($prevId) {
                $edges[] = [
                    'id' => "flow_{$prevId}_{$currentId}",
                    'source' => $prevId,
                    'target' => $currentId,
                    'type' => 'EVOLVED_TO'
                ];
            }
            $prevId = $currentId;
        }

        // 2. Myth Scar Relations
        $scars = \App\Modules\Narrative\Models\MythScar::where('universe_id', $universeId)->get();
        foreach ($scars as $scar) {
            // Find snapshots near the tick it was created
            $snapshot = UniverseSnapshot::where('universe_id', $universeId)
                ->where('tick', '>=', $scar->created_at_tick)
                ->orderBy('tick', 'asc')
                ->first();

            if ($snapshot) {
                $edges[] = [
                    'id' => "scar_inflict_{$scar->id}",
                    'source' => "snapshot_{$snapshot->id}",
                    'target' => "scar_{$scar->id}",
                    'type' => 'INFLICTED_BY'
                ];
            }
        }

        // 3. Ecology Relations (Latest Snapshot to Zones)
        $universe = Universe::where('id', $universeId)->first();
        $sv = is_string($universe->state_vector) ? json_decode($universe->state_vector, true) : ($universe->state_vector ?? []);
        $zones = $sv['zones'] ?? [];
        foreach ($zones as $idx => $zone) {
            $edges[] = [
                'id' => "ecology_link_{$universeId}_{$idx}",
                'source' => "universe_{$universeId}",
                'target' => "ecology_zone_{$universeId}_{$idx}",
                'type' => 'SUSTAINS'
            ];
        }

        // 4. Social Relations (Trust, Loyalty, Rivalry)
        $socialGraph = $sv['social_graph'] ?? [];
        foreach (['trust', 'loyalty', 'rivalry'] as $type) {
            $relations = $socialGraph[$type] ?? [];
            foreach ($relations as $rel) {
                // rel format: [from_id, to_id, value]
                if (is_array($rel) && count($rel) >= 2) {
                    $edges[] = [
                        'id' => "social_{$type}_{$rel[0]}_{$rel[1]}",
                        'source' => "actor_{$rel[0]}",
                        'target' => "actor_{$rel[1]}",
                        'type' => strtoupper($type),
                        'data' => [
                            'weight' => $rel[2] ?? 0.5
                        ]
                    ];
                }
            }
        }

        return $edges;
    }

    public function sync(int $universeId, array $data): bool
    {
        // Placeholder for real Neo4j sync later
        Log::info("GraphSync: Virtual synchronization completed for Universe {$universeId}. Relational projections updated.");
        return true;
    }
}

