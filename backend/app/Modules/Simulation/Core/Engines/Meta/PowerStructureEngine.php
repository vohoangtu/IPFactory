<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 71: Power Structure Engine 👑🏢
 * 
 * Mô phỏng phân bổ quyền lực, tầng lớp tinh hoa (elites) và các định chế (institutions).
 */
class PowerStructureEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'power_structure';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 11;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $stability = (float) $state->get('stability_index', 1.0);
        $fields = $state->getFields();
        
        // Quyền lực dựa trên Wealth + Military + Influence
        $powerConcentration = (float) ($fields['power'] ?? 0.5);
        $monolithicity = (float) $state->get('meta.institution_monolithicity', 0.5);

        // Nếu quyền lực quá tập trung nhưng độ ổn định thấp -> Dễ xảy ra đảo chính (Coup)
        if ($powerConcentration > 0.8 && $stability < 0.3) {
            Log::warning("Power Engine: High Coup Risk detected", [
                'power' => $powerConcentration,
                'stability' => $stability
            ]);
            $state->set('meta.coup_imminent', true);
        }

        // Cập nhật cấu trúc tinh hoa dựa trên sự thay đổi của các trường
        $newMonolithicity = $monolithicity + (($powerConcentration - 0.5) * 0.01);
        $state->set('meta.institution_monolithicity', max(0.0, min(1.0, $newMonolithicity)));

        return new EngineResult([], [], []);
    }
}
