<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Entities\InstitutionalEntity;
use Illuminate\Support\Facades\Log;

class DiplomaticResonanceEngine
{
    public function __construct(
        private InstitutionalRepositoryInterface $institutionalRepository
    ) {}

    /**
     * Cập nhật quan hệ ngoại giao giữa các nền văn minh.
     */
    public function updateRelationships(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $entities = $this->institutionalRepository->findActiveByUniverse($universe->id);
        $civilizations = array_filter($entities, fn($e) => $e->entityType === 'CIVILIZATION');

        if (count($civilizations) < 2) return;

        $stateVector = $snapshot->state_vector ?? [];
        $relations = $stateVector['diplomacy'] ?? [];

        $civsArray = array_values($civilizations);

        for ($i = 0; $i < count($civsArray); $i++) {
            for ($j = $i + 1; $j < count($civsArray); $j++) {
                $civA = $civsArray[$i];
                $civB = $civsArray[$j];
                
                $this->processBilateralRelation($universe, (int)$snapshot->tick, $civA, $civB, $relations);
            }
        }

        $stateVector['diplomacy'] = $relations;
        if ($snapshot->exists) {
            $snapshot->state_vector = $stateVector;
            $snapshot->save();
        } else {
            $uv = $universe->state_vector ?? [];
            $uv['diplomacy'] = $relations;
            $universe->state_vector = $uv;
            $universe->save();
        }
    }

    protected function processBilateralRelation(Universe $universe, int $tick, InstitutionalEntity $civA, InstitutionalEntity $civB, array &$relations): void
    {
        $friction = $this->calculateFriction($civA->ideologyVector, $civB->ideologyVector);
        $status = $this->determineStatus($friction);
        
        $key = $this->getRelationKey($civA->id, $civB->id);
        $oldStatus = $relations[$key]['status'] ?? 'NEUTRAL';

        $relations[$key] = [
            'status' => $status,
            'friction' => $friction,
            'updated_at_tick' => $tick,
            'participants' => [$civA->id, $civB->id]
        ];

        if ($status !== $oldStatus) {
            $this->logDiplomaticShift($universe, $tick, $civA, $civB, $status, "Quan hệ chuyển đổi từ $oldStatus thành $status.");
        }
    }

    public function calculateFriction(array $v1, array $v2): float
    {
        if (empty($v1) || empty($v2)) return 0.5;

        $dist = 0;
        $keys = array_unique(array_merge(array_keys($v1), array_keys($v2)));
        foreach ($keys as $key) {
            $val1 = $v1[$key] ?? 0.5;
            $val2 = $v2[$key] ?? 0.5;
            $dist += pow($val1 - $val2, 2);
        }
        
        $dist = sqrt($dist);
        return min(1.0, $dist / 1.5);
    }

    public function determineStatus(float $friction): string
    {
        if ($friction > 0.85) return 'WAR';
        if ($friction > 0.7) return 'HOSTILE';
        if ($friction < 0.2) return 'ALLIANCE';
        if ($friction < 0.4) return 'FRIENDLY';
        return 'NEUTRAL';
    }

    private function getRelationKey(int $id1, int $id2): string
    {
        $ids = [$id1, $id2];
        sort($ids);
        return "rel_{$ids[0]}_{$ids[1]}";
    }

    private function logDiplomaticShift(Universe $universe, int $tick, InstitutionalEntity $a, InstitutionalEntity $b, string $status, string $content): void
    {
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'diplomacy',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => "NGOẠI GIAO: {$a->name} & {$b->name} [$status] - $content"
        ]
        ]);

        Log::info("Diplomacy: {$a->name} & {$b->name} shift to $status.");
    }
}

