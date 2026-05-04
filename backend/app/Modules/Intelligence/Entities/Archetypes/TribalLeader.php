<?php

namespace App\Modules\Intelligence\Entities\Archetypes;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\BaseArchetype;

class TribalLeader extends BaseArchetype
{
    public function getName(): string
    {
        return 'TribalLeader';
    }

    public function getAttractorVector(): array
    {
        return [
            'culture'    =>  0.8,
            'militarism' =>  0.4,
            'tradition'  =>  0.5,
            'stability'  => -0.1,
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
                'Đại Hội Bộ Lạc',
                'Sức mạnh tập thể.',
                0.3,
                "Sức mạnh tập thể vượt qua mọi rào cản."
            )
        ];
    }
}

