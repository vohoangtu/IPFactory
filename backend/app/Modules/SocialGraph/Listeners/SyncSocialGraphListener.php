<?php

namespace App\Modules\SocialGraph\Listeners;

use App\Modules\Simulation\Core\Events\ActorBornEvent;
use App\Modules\Simulation\Core\Events\ActorDiedEvent;
use App\Modules\SocialGraph\Services\Neo4jSocialSyncer;
use Illuminate\Support\Facades\Log;

class SyncSocialGraphListener
{
    public function __construct(
        private readonly Neo4jSocialSyncer $syncer
    ) {}

    public function handleActorBorn(ActorBornEvent $event): void
    {
        $payload = $event->payload;
        $childId = $payload['child_id'];
        $p1 = $payload['parent1_id'];
        $p2 = $payload['parent2_id'];

        Log::info("SocialGraph: Creating node for newborn {$childId}.");

        // 1. Tạo node con
        $this->syncer->createActorNode($childId, "Newborn_{$childId}", 'villager', $event->universeId);

        // 2. Tạo quan hệ cha con
        $this->syncer->createParentChildRelation($p1, $childId);
        $this->syncer->createParentChildRelation($p2, $childId);
    }

    public function handleActorDied(ActorDiedEvent $event): void
    {
        $actorId = $event->payload['actor_id'];
        Log::info("SocialGraph: Marking actor {$actorId} as deceased in Graph.");
        $this->syncer->markActorDeceased($actorId);
    }
}
