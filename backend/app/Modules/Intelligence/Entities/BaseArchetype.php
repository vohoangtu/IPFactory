<?php

namespace App\Modules\Intelligence\Entities;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\Contracts\ActorArchetypeInterface;

abstract class BaseArchetype implements ActorArchetypeInterface
{
    abstract public function getName(): string;

    abstract public function getAttractorVector(): array;

    abstract public function isEligible(World $world): bool;

    /**
     * Default utility: dot product giữa civilization state và attractor vector.
     * Subclass có thể override nếu cần logic phức tạp hơn.
     */
    public function getBaseUtility(array $civilizationState): float
    {
        $score = 0.0;
        foreach ($this->getAttractorVector() as $key => $weight) {
            $score += ($civilizationState[$key] ?? 0.0) * $weight;
        }
        return $score;
    }

    /**
     * Tiện ích hỗ trợ tạo sẹo lịch sử (qua Domain Event).
     */
    protected function createScarEvent(
        Universe $universe,
        UniverseSnapshot $snapshot,
        string $name,
        string $desc,
        float $severity = 0.5
    ): \App\Modules\Intelligence\Events\ArchetypeImpactEvent {
        return new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
            universe: $universe,
            snapshot: $snapshot,
            scarName: $name,
            scarDesc: $desc,
            severity: $severity
        );
    }

    /**
     * @return \App\Modules\Intelligence\Events\ArchetypeImpactEvent[]
     */
    abstract public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array;
}

