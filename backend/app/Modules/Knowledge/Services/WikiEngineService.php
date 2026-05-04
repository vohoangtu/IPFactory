<?php

namespace App\Modules\Knowledge\Services;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Cosmology\AxiomRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WikiEngineService
{
    public function __construct(
        protected AxiomRegistry $axiomRegistry
    ) {}

    /**
     * Tìm kiếm đa nguồn (Axioms, Actors, Chronicles)
     */
    public function search(string $query, int $universeId): array
    {
        $actors = Actor::where('universe_id', $universeId)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'role', 'metrics'])
            ->map(function($actor) use ($universeId) {
                // Giả định có metadata về quan hệ từ Neo4j được cache trong metrics hoặc lấy riêng
                $actor->relationship_count = count($actor->metrics['relations'] ?? []); 
                return $actor;
            });

        $chronicles = Chronicle::where('universe_id', $universeId)
            ->where('title', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'title', 'tick', 'impact_score']);

        $axioms = collect($this->axiomRegistry->getAll())
            ->filter(fn($a) => stripos($a['name'], $query) !== false || stripos($a['id'], $query) !== false)
            ->map(function($a) use ($universeId) {
                // Thêm thông tin tiến hoá/biến động
                $a['drift_summary'] = $this->getAxiomDriftSummary($a['id'], $universeId);
                return $a;
            })
            ->take(5)
            ->values();

        return [
            'actors' => $actors,
            'chronicles' => $chronicles,
            'axioms' => $axioms,
            'metadata' => [
                'universe_id' => $universeId,
                'timestamp' => \Carbon\Carbon::now()->toIso8601String(),
            ]
        ];
    }

    /**
     * Tóm tắt biến động của Axiom (Evolutionary Logic)
     */
    public function getAxiomDriftSummary(string $axiomId, int $universeId): array
    {
        $logs = $this->getAxiomDriftLogs($axiomId, $universeId, 10);
        if ($logs->isEmpty()) {
            return ['status' => 'stable', 'drift' => 0];
        }

        $first = $logs->first()['value'];
        $last = $logs->last()['value'];
        
        $drift = is_numeric($first) && is_numeric($last) ? $last - $first : 0;
        $count = $logs->count();
        
        return [
            'status' => abs($drift) > 0.1 ? 'shifting' : 'stable',
            'drift' => $drift,
            'evolution_rate' => $count > 1 ? $drift / ($count - 1) : 0
        ];
    }

    /**
     * Tự động gắn link cho văn bản dựa trên các thực thể trong Universe.
     */
    public function autoLink(string $text, int $universeId): string
    {
        // 1. Link Actors
        $actors = Actor::where('universe_id', $universeId)->get(['id', 'name']);
        foreach ($actors as $actor) {
            $link = "<a href='/universes/{$universeId}/wiki/actor/{$actor->id}' class='wiki-link' data-type='actor' data-id='{$actor->id}'>{$actor->name}</a>";
            $text = str_replace($actor->name, $link, $text);
        }

        // 2. Link Axioms
        $axioms = $this->axiomRegistry->getAll();
        foreach ($axioms as $axiom) {
            $link = "<a href='/universes/{$universeId}/wiki/axiom/{$axiom['id']}' class='wiki-link' data-type='axiom' data-id='{$axiom['id']}'>{$axiom['name']}</a>";
            $text = str_ireplace($axiom['name'], $link, $text);
        }

        return $text;
    }

    /**
     * Lấy lịch sử biến động (Drift Logs) của một Axiom từ Snapshots.
     */
    public function getAxiomDriftLogs(string $axiomId, int $universeId, int $limit = 50): Collection
    {
        return UniverseSnapshot::where('universe_id', $universeId)
            ->orderBy('tick', 'asc')
            ->limit($limit)
            ->get(['tick', 'state_vector'])
            ->map(function ($snap) use ($axiomId) {
                // Giả định axiom nằm trong state_vector.axioms hoặc state_vector trực tiếp
                $sv = is_string($snap->state_vector) ? json_decode($snap->state_vector, true) : $snap->state_vector;
                $value = data_get($sv, "axioms.{$axiomId}") ?? data_get($sv, $axiomId);
                
                return [
                    'tick' => $snap->tick,
                    'value' => $value,
                ];
            })
            ->filter(fn($item) => !is_null($item['value']));
    }

    /**
     * Tìm các phiên bản song song của một thực thể (Cross-Universe Tracking)
     * Dựa trên identity_hash (nếu có) hoặc tên tương đối.
     */
    public function resolveParallelIdentities(Actor $actor): Collection
    {
        // Logic tìm kiếm Actor cùng tên hoặc cùng identity_hash trong các Universe khác
        $query = Actor::where('id', '!=', $actor->id)
            ->where('name', $actor->name);
            
        if (isset($actor->identity_hash)) {
            $query->orWhere('identity_hash', $actor->identity_hash);
        }

        return $query->with('universe:id,name')->limit(10)->get();
    }
}
