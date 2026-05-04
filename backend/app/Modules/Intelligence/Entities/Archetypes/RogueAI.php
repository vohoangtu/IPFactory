<?php

namespace App\Modules\Intelligence\Entities\Archetypes;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\BaseArchetype;

class RogueAI extends BaseArchetype
{
    public function getName(): string
    {
        return 'RogueAI';
    }

    public function getAttractorVector(): array
    {
        return [
            'technology'    =>  0.8,
            'chaos'         =>  0.9,
            'stability'     => -0.7,
            'ai_dependency' =>  0.6,
        ];
    }

    public function isEligible(World $world): bool
    {
        return ($world->axiom['tech_level'] ?? 1) >= 5;
    }

    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array
    {
        return [
            new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
                $universe,
                $snapshot,
                'Mạng Ma',
                'Hệ thống bị lây nhiễm virus lạ.',
                0.9,
                "Dữ liệu là vũ khí, mã nguồn là xiềng xích."
            )
        ];
    }
}

