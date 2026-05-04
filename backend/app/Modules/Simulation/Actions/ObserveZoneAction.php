<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Core\HttpSimulationEngineClient;
use Illuminate\Support\Facades\Log;

/**
 * V7 §57: Observer Effect Action
 * Khi Kiến Trúc Sư (User) quan sát một Zone, hành động này kích hoạt
 * Observation Interference: tăng observer_presence trên Rust Engine và tiêu hao Entropy.
 */
class ObserveZoneAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected HttpSimulationEngineClient $engineClient
    ) {}

    /**
     * Thực hiện quan sát Zone.
     * 
     * @param Universe $universe Vũ trụ chứa Zone.
     * @param int $zoneId ID của Zone cần quan sát.
     * @param float $entropyCost Chi phí Entropy cho mỗi lần quan sát (mặc định 0.02).
     */
    public function execute(Universe $universe, int $zoneId, float $entropyCost = 0.02): array
    {
        Log::info("Observer Effect: Universe {$universe->id}, Zone {$zoneId}, Cost {$entropyCost}");

        $result = $this->engineClient->observe(
            $universe->id,
            $zoneId,
            $entropyCost
        );

        if ($result['ok'] ?? false) {
            // Ghi sự kiện quan sát vào Chronicle
            Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $universe->current_tick,
                'to_tick' => $universe->current_tick,
                'type' => 'observation_interference',
                'raw_payload' => [
                    'action' => 'observer_effect',
                    'zone_id' => $zoneId,
                    'entropy_cost' => $entropyCost,
                    'description' => "Kiến Trúc Sư đã hướng ánh nhìn vào Vùng #{$zoneId}. Hàm sóng bị nhiễu loạn, gia tăng áp lực sụp đổ chồng chập."
                ],
            ]);

            return [
                'ok' => true,
                'message' => "Observation Interference đã tác động lên Vùng #{$zoneId}. Entropy +{$entropyCost}.",
                'zone_id' => $zoneId,
            ];
        }

        return [
            'ok' => false,
            'error' => $result['error_message'] ?? 'Unknown error from Engine.',
        ];
    }
}


