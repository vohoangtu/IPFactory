<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\AgentActionInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

class PropagateMythAction implements AgentActionInterface
{
    public function getType(): string
    {
        return 'propagate_myth';
    }

    public function execute(
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ActionResult {
        return new ActionResult(
            biographyEntry: "T{$tick}: Truyền bá đức tin cổ xưa, thắp sáng ngọn lửa thiêng giữa lòng dân chúng.",
            universeImpact: [
                'metrics.myth_intensity' => +0.05,
            ],
            chronicleEntry: [
                'type' => 'myth',
                'raw_payload' => [
                    'action'      => 'propagate_myth',
                    'description' => "{$actor->name} truyền bá đức tin cổ xưa, củng cố sợi dây liên kết vô hình.",
                ],
            ],
        );
    }
}

