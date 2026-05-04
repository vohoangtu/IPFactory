<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Services\ArchetypeResolverService;

class SpawnFromEventsAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private SpawnActorAction $spawnActorAction,
        private ActorRepositoryInterface $actorRepository,
        private ArchetypeResolverService $archetypeResolver
    ) {}

    public function handle(Universe $universe, int $tick): void
    {
        // 1. Spawn from significant events
        $events = BranchEvent::where('universe_id', $universe->id)
            ->where('event_type', 'micro_crisis')
            ->where('from_tick', '>=', $tick - 10)
            ->get();

        foreach ($events as $event) {
            $payload = $event->payload;
            if (isset($payload['winner'])) {
                $this->spawnActorAction->doExecute([
                    'universe_id' => $universe->id,
                    'name' => $payload['winner']['name'] ?? 'Vị Anh Hùng Vô Danh',
                    'archetype' => $payload['winner']['archetype'] ?? 'Kẻ Lang Thang',
                    'traits' => $payload['winner']['traits'] ?? null,
                    'biography' => "Ghi danh bảng vàng sau biến cố: " . ($payload['description'] ?? 'Loạn lạc'),
                    'metrics' => ['influence' => 1.0],
                    'spawned_at_tick' => $tick,
                ]);
            }
        }
        
        // 2. Ensure population minimum (configurable; 0 = off)
        $minPop = (int) config('worldos.intelligence.actor_minimum_population', 5);
        while ($minPop > 0 && $this->actorRepository->getActiveCount($universe->id) < $minPop) {
             $this->spawnSpontaneousActor($universe, $tick);
        }
    }

    private function spawnSpontaneousActor(Universe $universe, int $tick): void
    {
        $axiom = $universe->world?->axiom ?? [];
        $archetype = $this->archetypeResolver->resolve($axiom, $universe->entropy ?? 0.5, $universe->structural_coherence ?? 0.5);

        $this->spawnActorAction->doExecute([
            'universe_id' => $universe->id,
            'name' => "Nhân Vật " . rand(100, 999),
            'archetype' => $archetype,
            'biography' => "Cảm ứng thiên địa, xuất thế giữa lúc năng lượng dao động mạnh.",
            'metrics' => ['influence' => 0.5],
            'spawned_at_tick' => $tick,
        ]);
    }
}

