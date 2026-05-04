<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Events\SimulationEventOccurred;
use Illuminate\Support\Facades\DB;

/**
 * CausalExportService
 * Phase 3: Exports simulation causality data into a frontend-ready JSON format (Nodes and Links)
 * compatible with D3.js or other force-directed graph rendering libraries.
 */
class CausalExportService
{
    /**
     * Export the causal graph for a specific universe with cursor-based pagination.
     * 
     * @param Universe $universe
     * @param int $limit Maximum number of events per page.
     * @param int|null $cursor Last Chronicle ID seen (for pagination). null = start from beginning.
     * @return array{nodes: array, links: array, meta: array}
     */
    public function exportForUniverse(Universe $universe, int $limit = 100, ?int $cursor = null): array
    {
        $query = Chronicle::where('universe_id', $universe->id)
            ->orderBy('id', 'asc')
            ->limit($limit + 1); // Fetch 1 extra to detect if there are more pages

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $chronicles = $query->get();

        // Determine pagination state
        $hasMore = $chronicles->count() > $limit;
        if ($hasMore) {
            $chronicles = $chronicles->take($limit); // Remove the lookahead record
        }

        $nodes = [];
        $links = [];
        $previousChronicleId = null;
        $lastId = null;

        foreach ($chronicles as $chunk) {
            $nodeId = 'chronicle_' . $chunk->id;
            $lastId = $chunk->id;
            
            // Generate Node
            $nodes[] = [
                'id'      => $nodeId,
                'db_id'   => $chunk->id,
                'group'   => $this->determineGroup($chunk->type),
                'label'   => $chunk->type,
                'tick'    => $chunk->tick,
                'content' => $chunk->content ?? ($chunk->raw_payload['description'] ?? 'Event occurred'),
            ];

            // Causality 2.0: Targeted Link (Deep Causality)
            if ($chunk->parent_id) {
                $links[] = [
                    'source' => 'chronicle_' . $chunk->parent_id,
                    'target' => $nodeId,
                    'type'   => 'deep',
                    'value'  => 2, // Stronger visual link
                ];
            } elseif ($previousChronicleId !== null) {
                // Fallback: Chronological causal link (Shallow Causality)
                $links[] = [
                    'source' => $previousChronicleId,
                    'target' => $nodeId,
                    'type'   => 'chronological',
                    'value'  => 1,
                ];
            }

            $previousChronicleId = $nodeId;
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'meta'  => [
                'count'       => count($nodes),
                'has_more'    => $hasMore,
                'next_cursor' => $hasMore ? $lastId : null,
            ],
        ];
    }

    /**
     * Helper to assign color groups to event types for D3.js
     */
    protected function determineGroup(string $type): int
    {
        $type = strtolower($type);
        if (str_contains($type, 'rebirth') || str_contains($type, 'primordial')) {
            return 1; // Foundation events
        }
        if (str_contains($type, 'collapse') || str_contains($type, 'extinct') || str_contains($type, 'died')) {
            return 2; // Destructive/End events
        }
        if (str_contains($type, 'anomaly') || str_contains($type, 'omen')) {
            return 3; // AI/Meta events
        }
        if (str_contains($type, 'transition') || str_contains($type, 'evolution')) {
            return 4; // Evolutionary events
        }
        
        return 0; // Standard events
    }
}


