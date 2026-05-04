<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\CausalTrajectory;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class RecordEventHorizonAction
implements \App\Contracts\ActionInterface {
    /**
     * Ghi nhận một chân trời sự kiện trong quỹ đạo nhân quả.
     */
    public function execute(Universe $universe, int $currentTick, array $trajectoryData): CausalTrajectory
    {
        $targetTick = $currentTick + ($trajectoryData['distance'] ?? 50);
        $description = $trajectoryData['phenomenon_description'];
        $type = $trajectoryData['convergence_type'] ?? 'phenomenon';
        $probability = $trajectoryData['probability'] ?? 0.5;

        // 1. Tạo bản ghi Quỹ đạo nhân quả
        $trajectory = CausalTrajectory::create([
            'universe_id' => $universe->id,
            'target_tick' => $targetTick,
            'phenomenon_description' => $description,
            'probability' => $probability,
            'convergence_type' => $type,
            'is_fulfilled' => false,
        ]);

        // 2. Sử gia ghi chép (Perceived Archive)
        $narrative = $this->generateCausalNarrative($description, $targetTick);
        
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $currentTick,
            'to_tick' => $currentTick,
            'type' => 'causal_trajectory',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'target_tick' => $targetTick,
                'convergence_type' => $type,
                'probability' => $probability
            ]
        ]);

        // 3. Phát động sự kiện nhân quả (System Event)
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $currentTick,
            'event_type' => 'event_horizon_detected',
            'payload' => [
                'trajectory_id' => $trajectory->id,
                'target_tick' => $targetTick,
                'description' => $description,
                'probability' => $probability
            ],
        ]);

        Log::info("Event Horizon Recorded: [{$description}] at Tick {$targetTick} (Universe {$universe->id})");

        return $trajectory;
    }

    protected function generateCausalNarrative(string $description, int $targetTick): string
    {
        return "GHI CHÉP NHÂN QUẢ: Các quan sát tại Tick hiện tại cho thấy một sự hội tụ đáng kể về hướng mục tiêu {$targetTick}. " .
               "Mô tả hiện tượng: \"{$description}\". " .
               "Thực tại đang tự cấu trúc lại theo quỹ đạo này.";
    }
}

