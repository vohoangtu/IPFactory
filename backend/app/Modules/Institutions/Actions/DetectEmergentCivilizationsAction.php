<?php

namespace App\Modules\Institutions\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Entities\InstitutionalEntity;
use Illuminate\Support\Facades\Log;

class DetectEmergentCivilizationsAction
implements \App\Contracts\ActionInterface {
    protected float $similarityThreshold = 0.85;
    protected int $minClusterSize = 2;

    public function __construct(
        private InstitutionalRepositoryInterface $institutionalRepository,
        private SpawnInstitutionAction $spawnAction
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $zones = ($universe->state_vector ?? [])['zones'] ?? [];
        if (empty($zones)) return;

        // 1. Find cultural clusters
        $clusters = $this->findCulturalClusters($zones);

        // 2. Manage civilizations based on clusters
        foreach ($clusters as $zoneIds) {
            $this->ensureCivilizationExists($universe, $zoneIds, $zones, (int)$snapshot->tick);
        }
    }

    protected function findCulturalClusters(array $zones): array
    {
        $clusters = [];
        $visited = [];
        $idMap = [];
        foreach ($zones as $index => $z) {
            $idMap[$z['id']] = $index;
        }

        foreach ($zones as $index => $zone) {
            $id = $zone['id'];
            if (isset($visited[$id])) continue;

            $currentCluster = [];
            $queue = [$index];
            $visited[$id] = true;

            while (!empty($queue)) {
                $currIdx = array_shift($queue);
                $currZone = $zones[$currIdx];
                $currentCluster[] = $currZone['id'];

                $neighbors = $currZone['neighbors'] ?? [];
                foreach ($neighbors as $neighborId) {
                    if (isset($visited[$neighborId])) continue;
                    if (!isset($idMap[$neighborId])) continue;
                    
                    $neighborIdx = $idMap[$neighborId];
                    if ($this->isCulturallySimilar($currZone, $zones[$neighborIdx])) {
                        $visited[$neighborId] = true;
                        $queue[] = $neighborIdx;
                    }
                }
            }

            if (count($currentCluster) >= $this->minClusterSize) {
                $clusters[] = $currentCluster;
            }
        }

        return $clusters;
    }

    protected function isCulturallySimilar(array $z1, array $z2): bool
    {
        $v1 = $z1['state']['cultural'] ?? [];
        $v2 = $z2['state']['cultural'] ?? [];
        if (empty($v1) || empty($v2)) return false;

        $dimensions = ['tradition_rigidity', 'innovation_openness', 'collective_trust', 'violence_tolerance', 'institutional_respect'];
        $sumSqDiff = 0;
        foreach ($dimensions as $dim) {
            $val1 = $v1[$dim] ?? 0.5;
            $val2 = $v2[$dim] ?? 0.5;
            $sumSqDiff += pow($val1 - $val2, 2);
        }

        $distance = sqrt($sumSqDiff);
        $similarity = 1 - ($distance / 2.24); // sqrt(5) ~ 2.236

        return $similarity >= $this->similarityThreshold;
    }

    protected function ensureCivilizationExists(Universe $universe, array $zoneIds, array $allZones, int $tick): void
    {
        // Simple heuristic: if a civ already occupies majority of these zones, we don't spawn a new one.
        // For now, let's keep it simple and just spawn if none exists in the primary zone.
        $primaryZone = $zoneIds[0];
        
        $existing = $this->institutionalRepository->findActiveByUniverse($universe->id);
        foreach ($existing as $entity) {
            if ($entity->entityType === 'CIVILIZATION' && isset($entity->influenceMap[$primaryZone])) {
                // Update influence map to include the cluster
                foreach ($zoneIds as $id) {
                    $entity->influenceMap[$id] = max($entity->influenceMap[$id] ?? 0, 0.5);
                }
                $this->institutionalRepository->save($entity);
                return;
            }
        }

        // None found, spawn new
        $this->spawnAction->doExecute($universe, $primaryZone, $tick, 'CIVILIZATION');
    }
}

