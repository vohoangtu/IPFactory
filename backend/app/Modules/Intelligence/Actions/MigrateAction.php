<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\AgentActionInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

class MigrateAction implements AgentActionInterface
{
    public function getType(): string
    {
        return 'migrate';
    }

    public function execute(
        ActorEntity     $actor,
        Universe        $universe,
        UniverseContext $ctx,
        int             $tick,
    ): ActionResult {
        // Vary the biography text so it doesn't repeat verbatim
        $phrases = [
            "Rời bỏ chốn cũ, lên đường tìm kiếm chân trời mới.",
            "Quyết định dời bước, bắt đầu hành trình vượt biên giới.",
            "Cuộc di cư bắt đầu — thế giới cũ đã không còn chỗ cho chí hướng này.",
            "Chấp nhận rủi ro vô định, dấn thân vào vùng đất chưa ai đặt chân.",
            "Hành trình mới bắt đầu từ một quyết định đơn độc.",
        ];

        $phrase = $phrases[array_rand($phrases)];

        return new ActionResult(
            biographyEntry: "T{$tick}: {$phrase}",
            universeImpact: [
                'entropy' => +0.01, // minor turbulence from migration
            ],
            chronicleEntry: null, // migrate is a personal event, not always world-scale
        );
    }
}

