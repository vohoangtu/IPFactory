<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 66: Infinite Recursion Engine (V9 Core) 🔄🌀
 * 
 * Nền văn minh đạt tới cấp độ có thể tự chạy "Giả lập" của riêng họ.
 * Tạo ra các Nested WorldStates và cơ chế rò rỉ dữ liệu (Information Leakage).
 */
class InfiniteRecursionEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'infinite_recursion';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 15;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $fields = $state->getFields();
        $meaning = $fields['meaning'] ?? 0.0;
        $knowledge = $fields['knowledge'] ?? 0.0;

        // 1. Kích hoạt đệ quy khi Knowledge & Meaning vượt ngưỡng 0.9
        if ($knowledge > 0.9 && $meaning > 0.9) {
            $nested = $state->getNestedRealities();
            
            if (count($nested) < 3) { // Giới hạn 3 tầng đệ quy để tránh stack overflow hiệu năng
                Log::info("InfiniteRecursionEngine: Level " . count($nested) . " civilization is booting their own WorldOS.");
                
                // Khởi tạo một sub-state đơn giản hóa từ state hiện tại
                $subData = [
                    'entropy' => 0.1,
                    'fields' => array_map(fn($v) => $v * 0.5, $state->getFields()),
                ];
                
                $state->pushNestedReality($subData);
            }
        }

        // 2. Information Leakage (Rò rỉ thông tin từ tầng sâu lên tầng nông)
        $nested = $state->getNestedRealities();
        foreach ($nested as &$reality) {
            // "Nghịch lý nhân quả": Hành động trong sub-simulation tác động ngược lại Level 0 core fields
            $leakage = $reality['leakage_factor'] ?? 0.01;
            $subKnowledge = $reality['data']['fields']['knowledge'] ?? 0.0;
            
            if ($subKnowledge > 0.8) {
                // Tăng nhẹ Knowledge ở Level 0 do "cảm hứng" từ sub-sim
                $fields = $state->getFields();
                $fields['knowledge'] = min(1.0, ($fields['knowledge'] ?? 0.0) + ($subKnowledge * $leakage));
                $state->setFields($fields);
                
                Log::info("InfiniteRecursionEngine: Causal Leakage detected from Sub-Layer " . $reality['layer']);
            }
            
            // Tiến hóa sub-simulation theo thời gian (giản lược)
            $reality['data']['fields']['knowledge'] = min(1.0, ($reality['data']['fields']['knowledge'] ?? 0.0) + 0.005);
        }
        
        $state->setNestedRealities($nested);

        return EngineResult::empty();
    }
}
