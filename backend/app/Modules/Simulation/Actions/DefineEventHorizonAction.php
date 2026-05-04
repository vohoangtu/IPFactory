<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\TrajectoryEntity;
use App\Modules\Simulation\Contracts\TrajectoryRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

class DefineEventHorizonAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private TrajectoryRepositoryInterface $trajectoryRepository
    ) {}

    /**
     * Xác lập một ngã rẽ tiềm năng trong Biên niên sử (Event Horizon).
     */
    public function execute(UniverseEntity $universe, int $tick, array $data): TrajectoryEntity
    {
        $trajectory = new TrajectoryEntity(
            id: null,
            universeId: $universe->id,
            targetTick: $data['target_tick'],
            phenomenonDescription: $data['description'],
            probability: $data['probability'] ?? 1.0,
            convergenceType: $data['type'] ?? 'custom_event'
        );

        $saved = $this->trajectoryRepository->save($trajectory);

        // Ghi chép Sử gia về Đường chân trời sự kiện
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'event_horizon_defined',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "ĐƯỜNG CHÂN TRỜI SỰ KIỆN: Sử gia đã xác định một điểm hội tụ mới [{$saved->phenomenonDescription}] tại tick {$saved->targetTick}."
            ],
            'perceived_archive_snapshot' => [
                'trajectory_id' => $saved->id,
                'probability' => $saved->probability
            ]
        ]);

        Log::info("Event Horizon Defined: [{$saved->phenomenonDescription}] in Universe {$universe->id}");

        return $saved;
    }
}

