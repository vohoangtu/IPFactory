<?php

namespace App\Modules\Narrative\Listeners;

use App\Modules\Simulation\Core\Events\ActorBornEvent;
use App\Modules\Simulation\Core\Events\ActorDiedEvent;
use App\Modules\Narrative\Services\NarrativeEngine;
use Illuminate\Support\Facades\Log;

class ChronicleLifeEventListener
{
    public function __construct(
        private readonly NarrativeEngine $narrativeEngine
    ) {}

    public function handleActorBorn(ActorBornEvent $event): void
    {
        $payload = $event->payload;
        $childId = $payload['child_id'];

        Log::info("Narrative: Chronidling birth of actor {$childId}.");

        // Tự động kích hoạt gen narrative cho sự kiện sinh nở
        // (Tương lai sẽ gọi LLM để viết prose cảm động)
        $this->narrativeEngine->createChronicle(
            $event->universeId,
            "Birth",
            "Sự ra đời của một linh hồn mới ({$childId}) đã thắp sáng thực tại.",
            ['actor_id' => $childId, 'tick' => $event->tick]
        );
    }

    public function handleActorDied(ActorDiedEvent $event): void
    {
        $actorId = $event->payload['actor_id'];
        Log::info("Narrative: Chronidling death of actor {$actorId}.");

        $this->narrativeEngine->createChronicle(
            $event->universeId,
            "Death",
            "Cát bụi lại trở về với cát bụi. Actor {$actorId} đã kết thúc hành trình của mình.",
            ['actor_id' => $actorId, 'tick' => $event->tick]
        );
    }
}
