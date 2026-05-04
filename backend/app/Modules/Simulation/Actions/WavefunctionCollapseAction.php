<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

class WavefunctionCollapseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private UniverseRepositoryInterface $universeRepository
    ) {}

    /**
     * Thực thi sự sụp đổ hàm sóng thực tại do hiện tượng nhiễu xạ quan sát.
     */
    public function execute(UniverseEntity $universe, int $tick): void
    {
        // Legacy bridge
    }

    public function executeWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $oldLoad = (float)$state->get('meta.observation_load', 0.0);
        $intensity = 0.2; 
        
        // Apply deterministic impacts to manifold fields
        $newLoad = $oldLoad + $intensity;
        $state->set('meta.observation_load', $newLoad);
        
        $currentEntropy = $state->getEntropy();
        $state->setEntropy(max(0.0, $currentEntropy - ($intensity * 0.05)));
        
        $currentStability = (float)$state->get('stability_index', 0.5);
        $state->set('stability_index', min(1.0, $currentStability + ($intensity * 0.1)));

        // Ghi chép Sử gia (Chronicle creation via State triggers or directly)
        if (floor($newLoad) > floor($oldLoad)) {
            $this->logStateNarrative($state, $tick, $newLoad);
        }

        Log::debug("Wavefunction Collapse executed for Universe {$state->get('universe_id')} via manifold. New Load: {$newLoad}");
    }

    protected function logStateNarrative(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick, float $newLoad): void
    {
        $narrative = "SỰ NGƯNG TRỆ CỦA CÁC KHẢ NĂNG: Áp lực từ Đệ nhất Quan sát nhân đã đạt mức " . number_format($newLoad, 1) . " Φ. " .
                     "Các biến số ngẫu nhiên đang bị nén chặt vào một thực tại duy nhất.";

        Chronicle::create([
            'universe_id' => $state->get('universe_id'),
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'observation_interference',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'interference_load' => $newLoad,
                'state' => $newLoad > 8 ? 'saturation' : 'collapse'
            ]
        ]);
    }

    protected function logObservationNarrative(UniverseEntity $universe, int $tick): void
    {
        $narrative = "SỰ NGƯNG TRỆ CỦA CÁC KHẢ NĂNG: Áp lực từ Đệ nhất Quan sát nhân đã đạt mức " . number_format($universe->observationLoad, 1) . " Φ. " .
                     "Các biến số ngẫu nhiên đang bị nén chặt vào một thực tại duy nhất. " .
                     "Trật tự lượng tử đang được thiết đặt một cách cưỡng bách.";

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'observation_interference',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'interference_load' => $universe->observationLoad,
                'state' => $universe->observationLoad > 8 ? 'saturation' : 'collapse'
            ]
        ]);
    }
}


