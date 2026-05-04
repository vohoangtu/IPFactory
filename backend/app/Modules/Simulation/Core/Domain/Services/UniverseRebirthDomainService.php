<?php

namespace App\Modules\Simulation\Core\Domain\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use Illuminate\Support\Facades\Log;
use App\Modules\Simulation\Core\SimulationEventBus;
use App\Modules\Simulation\Core\Events\UniverseRebirthEvent;

class UniverseRebirthDomainService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository,
        protected UniverseSnapshotRepository $snapshotRepository,
        protected SimulationEventBus $eventBus
    ) {}

    /**
     * Rebirth: Create primordial state and activate the universe for the new epoch.
     */
    public function rebirthUniverse(Universe $universe): void
    {
        $epoch = $universe->epoch ?? 1;
        $tick = $universe->current_tick ?? 0;

        // 1. Create primordial snapshot — tabula rasa state
        $primordialState = [
            'entropy'       => 0.5,
            'stability'     => 0.3,
            'knowledge'     => 0.01,
            'technology'    => 0.01,
            'institution'   => 0.01,
            'economy'       => 0.1,
            'militarism'    => 0.1,
            'population'    => 0.2,
            'inequality'    => 0.1,
            'culture'       => 0.05,
            'spirituality'  => 0.1,
            'environment'   => 0.9,
            'ai_dependency' => 0.0,
        ];

        // Use SnapshotRepository for persistence
        $this->snapshotRepository->save($universe, [
            'tick'            => $tick,
            'state_vector'    => $primordialState,
            'entropy'         => 0.5,
            'stability_index' => 0.3,
            'metrics'         => [
                'order'        => 0.05,
                'energy_level' => 0.1,
                'entropy'      => 0.5,
                'epoch'        => $epoch,
                'rebirth'      => true,
            ],
        ]);

        // 2. Reset universe state vector via UniverseRepository
        $this->universeRepository->update($universe->id, [
            'status'       => 'active',
            'state_vector' => $primordialState,
        ]);

        // 3. Chronicle: record the rebirth (Fallback to Eloquent directly until ChronicleRepository is available)
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick'   => $tick,
            'to_tick'     => $tick,
            'type'        => 'primordial_rebirth',
            'raw_payload' => [
                'action'      => 'legacy_event',
                'description' => "Từ tro tàn của kỷ nguyên cũ, hỗn nguyên lại khai mở. Epoch {$epoch} bắt đầu trong sự im lặng của vạn vật sơ khai.",
            ],
        ]);

        Log::info("PRIMORDIAL REBIRTH: Universe #{$universe->id} reborn into Epoch {$epoch} at tick {$tick}.");

        // 4. Dispatch the newly created Typed Event for Rebirth
        $this->eventBus->dispatch(new UniverseRebirthEvent(
            $universe->id, 
            $tick, 
            ['epoch' => $epoch, 'seed_state' => $primordialState]
        ));
    }
}

