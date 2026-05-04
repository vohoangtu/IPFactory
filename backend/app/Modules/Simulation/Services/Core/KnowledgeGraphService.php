<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Knowledge\Models\Idea;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Doc §9: Knowledge graph — nodes from Ideas/Artifacts, edges (stub prerequisite/derived_from).
 * Writes state_vector['knowledge_graph'] = { nodes: [...], edges: [...], updated_tick }.
 */
final class KnowledgeGraphService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    public function evaluate(Universe $universe, int $currentTick): void
    {
        $interval = (int) config('worldos.knowledge_graph.interval', 10);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        if (config('worldos.simulation.rust_authoritative', false)) {
            $stateVector = $this->getStateVector($universe);
            if (isset($stateVector['knowledge_graph'])) {
                return;
            }
        }

        $ideas = Idea::where('universe_id', $universe->id)->get();
        $nodes = [];
        foreach ($ideas as $idea) {
            $nodes[] = [
                'id' => 'idea_' . $idea->id,
                'type' => $idea->info_type ?? 'meme',
                'knowledge_level' => min(1.0, max(0.0, (float) ($idea->influence_score ?? 0) / 100.0)),
                'followers' => (float) ($idea->followers ?? 0),
            ];
        }

        $edges = $this->buildStubEdges($ideas);

        $stateVector = $this->getStateVector($universe);
        $stateVector['knowledge_graph'] = [
            'nodes' => array_slice($nodes, 0, (int) config('worldos.knowledge_graph.max_nodes', 500)),
            'edges' => array_slice($edges, 0, (int) config('worldos.knowledge_graph.max_edges', 200)),
            'updated_tick' => $currentTick,
        ];
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        Log::debug("KnowledgeGraphService: Universe {$universe->id} knowledge_graph updated at tick {$currentTick}");
    }

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }

    /**
     * Build edges: derived_from (by info_type + knowledge_level order), prerequisite (by config).
     */
    private function buildStubEdges($ideas): array
    {
        $list = $ideas->sortBy('birth_tick')->values()->all();
        $byId = [];
        foreach ($list as $idea) {
            $byId[$idea->id] = $idea;
        }
        $edges = [];
        $derivedFromTypes = config('worldos.knowledge_graph.derived_from_types', []);
        foreach ($list as $idea) {
            $sourceTypes = $derivedFromTypes[$idea->info_type ?? 'meme'] ?? [];
            foreach ($list as $other) {
                if ($other->id >= $idea->id) {
                    continue;
                }
                if (in_array($other->info_type ?? 'meme', $sourceTypes, true)) {
                    $edges[] = [
                        'source_idea_id' => $other->id,
                        'target_idea_id' => $idea->id,
                        'relation_type' => 'derived_from',
                        'relation' => 'derived_from',
                        'from' => 'idea_' . $other->id,
                        'to' => 'idea_' . $idea->id,
                    ];
                }
            }
        }
        foreach ($edges as &$e) {
            if (! isset($e['relation'])) {
                $e['relation'] = $e['relation_type'] ?? 'derived_from';
            }
        }
        unset($e);
        return array_slice($edges, 0, (int) config('worldos.knowledge_graph.max_edges', 200));
    }
}

