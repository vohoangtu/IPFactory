<?php

namespace App\Modules\Intelligence\Entities\Archetypes;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\BaseArchetype;

class Warlord extends BaseArchetype
{
    public function getName(): string
    {
        return 'Warlord';
    }

    public function getAttractorVector(): array
    {
        return [
            'militarism'  =>  0.9,
            'chaos'       =>  0.7,
            'stability'   => -0.5,
            'trauma'      =>  0.4,
        ];
    }

    public function isEligible(World $world): bool
    {
        return ($world->axiom['entropy_rate'] ?? 1.0) > 0.6;
    }

    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array
    {
        return [
            new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
                $universe,
                $snapshot,
                'Huyết Chiến',
                'Một cuộc thanh trừng quy mô lớn đã diễn ra.',
                0.8,
                "Binh đao loạn lạc, kẻ mạnh sinh tồn."
            )
        ];
    }
}

