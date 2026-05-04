<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\TrajectoryEntity;
use App\Modules\Simulation\Contracts\TrajectoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

class TrajectoryModelingEngine
{
    public function __construct(
        protected TrajectoryRepositoryInterface $trajectoryRepository
    ) {}

    /**
     * Phân tích các xu hướng nhân quả và mô phỏng các biến cố tiềm tàng.
     */
    public function process(UniverseEntity $universe, int $tick): void
    {
        // Chỉ dự báo mỗi 1000 tick
        if ($tick % 1000 !== 0) return;

        $stability = $universe->stabilityIndex;
        $entropy = $universe->entropy;

        if ($entropy > 0.7) {
            $this->projectTrajectory($universe, $tick, 'catastrophic_collapse');
        } elseif ($stability > 0.9) {
            $this->projectTrajectory($universe, $tick, 'stagnation_point');
        }
    }

    protected function projectTrajectory(UniverseEntity $universe, int $tick, string $type): void
    {
        $targetTick = $tick + mt_rand(5000, 10000);
        $probability = mt_rand(30, 80) / 100.0;

        $trajectory = new TrajectoryEntity(
            id: null,
            universeId: $universe->id,
            targetTick: $targetTick,
            phenomenonDescription: "Dự báo: Một điểm hội tụ loại [{$type}] có khả năng cao sẽ xảy ra.",
            probability: $probability,
            convergenceType: $type
        );

        $this->trajectoryRepository->save($trajectory);
        
        Log::info("New Trajectory Projected for Universe {$universe->id}: {$type} at tick {$targetTick}");
    }
}
