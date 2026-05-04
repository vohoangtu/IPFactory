<?php

namespace App\Modules\Intelligence\Entities\Archetypes;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\BaseArchetype;

class VillageElder extends BaseArchetype
{
    public function getName(): string
    {
        return 'VillageElder';
    }

    public function getAttractorVector(): array
    {
        return [
            'tradition' =>  0.9,
            'stability' =>  0.5,
            'chaos'     => -0.4,
            'culture'   =>  0.6,
        ];
    }

    public function isEligible(World $world): bool
    {
        return true;
    }

    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array
    {
        return [
            new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
                $universe,
                $snapshot,
                'Thư Viện Cổ',
                'Kinh nghiệm truyền lại cho thế hệ sau.',
                0.2,
                "Kinh nghiệm là tài sản quý báu nhất của bộ lạc."
            )
        ];
    }
}

