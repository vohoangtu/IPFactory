<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 71: Information Propagation Engine 📡🗣️
 * 
 * Mô phỏng việc lan truyền tin đồn (rumors), tuyên truyền (propaganda) và niềm tin.
 */
class InformationPropagationEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'information_propagation';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 10;
    }

    public function tickRate(): int
    {
        return 5;
    }

    /**
     * Xử lý lan truyền thông tin giữa các thực thể
     */
    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $packets = $state->get('meta.information_packets', []);
        $resonance = (float) $state->get('resonance_field', 0.5);

        foreach ($packets as &$packet) {
            // Độ tin cậy giảm dần theo thời gian (Decay)
            $packet['credibility'] *= 0.98;

            // Nếu độ cộng hưởng cao, tin đồn lan truyền nhanh hơn
            $spreadFactor = $packet['virality'] * $resonance;
            
            if ($spreadFactor > 0.8) {
                Log::info("Information Engine: High virality packet detected", ['type' => $packet['type']]);
                // Kích hoạt các hiệu ứng xã hội
                $state->set('meta.social_unrest', (float)$state->get('meta.social_unrest', 0) + 0.05);
            }
        }

        $state->set('meta.information_packets', $packets);

        return new EngineResult([], [], []);
    }
}
