<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 67: Idealism Engine (V9 Final Core) 🧠✨
 * 
 * "Vật chất chỉ là hình xạ của Ý chí."
 * Engine này cho phép niềm tin tập thể (Collective Belief) thay đổi các hằng số vật lý.
 */
class IdealismEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'idealism';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 9;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $fields = $state->getFields();
        $belief = $fields['belief'] ?? 0.0;
        $will = $fields['will'] ?? 0.0;

        // Nếu Belief & Will cực cao, thực tại bắt đầu bị bẻ cong theo ý muốn
        if ($belief > 0.85 && $will > 0.85) {
            Log::info("IdealismEngine: Collective intent is reshaping physical axioms.");

            // 1. Tác động lên Entropy: Ý chí có thể đảo ngược nhiệt động lực học
            if (isset($fields['entropy'])) {
                $fields['entropy'] = max(0.0, $fields['entropy'] - ($will * 0.05));
            }

            // 2. Tác động lên Causal Integrity: Thực tại trở nên linh hoạt hơn (looser)
            // Cho phép các sự kiện "phép màu" (miracles) xảy ra dễ dàng hơn
            $state->setCosmic(array_merge($state->getCosmic(), [
                'idealism_active' => true,
                'subjective_multiplier' => 1.0 + ($belief * 0.5)
            ]));
        }

        // 3. Reshape hằng số từ Hyperspace Vector dựa trên Idealism
        $hyperspace = $state->getHyperspaceVector();
        if (!empty($hyperspace) && $belief > 0.9) {
            // "Alchemical Transmutation": Biến đổi Hyperspace thành tài nguyên thực thể
            $fields['resource_abundance'] = ($fields['resource_abundance'] ?? 0.0) + ($hyperspace[10] ?? 0.0) * 0.1;
        }

        $state->setFields($fields);

        return EngineResult::empty();
    }
}
