<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\AgentActionInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

class RevoltAction implements AgentActionInterface
{
    public function getType(): string
    {
        return 'revolt';
    }

    public function execute(
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ActionResult {
        return new ActionResult(
            biographyEntry: "T{$tick}: Bùng nổ nộ khí, công khai phản kháng lại trật tự hiện hành.",
            universeImpact: [
                'entropy'         => +0.05,
                'stability_index' => -0.05,
            ],
            chronicleEntry: [
                'type' => 'revolt',
                'raw_payload' => [
                    'action'      => 'revolt',
                    'description' => "BIẾN LOẠN: {$actor->name} công khai phản kháng, tạo ra một cơn sóng bất ổn lan rộng.",
                ],
            ],
        );
    }
}

