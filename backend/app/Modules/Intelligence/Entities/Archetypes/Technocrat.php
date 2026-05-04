<?php

namespace App\Modules\Intelligence\Entities\Archetypes;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\BaseArchetype;

class Technocrat extends BaseArchetype
{
    public function getName(): string
    {
        return 'Technocrat';
    }

    public function getAttractorVector(): array
    {
        return [
            'technology' =>  0.9,
            'stability'  =>  0.7,
            'knowledge'  =>  0.5,
            'tradition'  => -0.2,
        ];
    }

    public function isEligible(World $world): bool
    {
        return ($world->axiom['material_organization'] ?? false) === true;
    }

    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array
    {
        return [
            new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
                $universe,
                $snapshot,
                'Trật Tự Kỷ Nguyên',
                'Máy móc thay thế một phần quyền lực.',
                0.5,
                "Trật tự và hiệu suất được ưu tiên hàng đầu."
            )
        ];
    }
}

