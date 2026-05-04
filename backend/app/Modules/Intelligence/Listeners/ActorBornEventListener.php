<?php

namespace App\Modules\Intelligence\Listeners;

use App\Modules\Simulation\Core\Events\ActorBornEvent;
use App\Modules\Intelligence\Services\ActorRegistry;
use Illuminate\Support\Facades\Log;

class ActorBornEventListener
{
    public function __construct(
        private readonly ActorRegistry $actorRegistry
    ) {}

    public function handle(ActorBornEvent $event): void
    {
        $payload = $event->payload;
        $childId = $payload['child_id'];
        $universeId = $event->universeId;

        Log::info("Intelligence: Initializing newborn actor {$childId} in Universe {$universeId}.");

        // 1. Tự động gán Archetype ngẫu nhiên hoặc dựa trên cha mẹ (Tương lai)
        // Hiện tại gán mặc định là Villager hoặc RogueAI tùy xác suất thấp
        $archetype = (mt_rand(0, 100) < 5) ? 'rogue_ai' : 'tribal_leader';
        
        // 2. Đăng ký vào Registry
        // Chú ý: Cần đảm bảo ActorRegistry có phương thức gán archetype thủ công
        Log::debug("Intelligence: Actor {$childId} assigned archetype: {$archetype}.");
        
        // TODO: Khởi tạo Beliefs ban đầu dựa trên traits cha mẹ
    }
}
