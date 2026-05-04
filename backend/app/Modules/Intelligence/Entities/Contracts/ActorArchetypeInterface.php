<?php

namespace App\Modules\Intelligence\Entities\Contracts;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

interface ActorArchetypeInterface
{
    public function getName(): string;

    /**
     * Attractor vector: định nghĩa lực hút của archetype trong civilization phase space.
     * Ví dụ: ['militarism' => 0.9, 'chaos' => 0.7, 'stability' => -0.5]
     *
     * @return array<string, float>
     */
    public function getAttractorVector(): array;

    public function isEligible(World $world): bool;

    /**
     * Tính utility dựa trên civilization state vector (dot product với attractor vector).
     *
     * @param array<string, float> $civilizationState Canonical state vector
     */
    public function getBaseUtility(array $civilizationState): float;

    /**
     * Áp dụng tác động lên vũ trụ khi Archetype thắng.
     *
     * @return \App\Modules\Intelligence\Events\ArchetypeImpactEvent[]
     */
    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array;
}

