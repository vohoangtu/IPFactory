<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 69: Information Density Engine (V10 Terminal Horizon) 🧱🛰️
 * 
 * "Thực tại bị ghì chặt bởi khối lượng dữ liệu của chính nó."
 * Engine này tính toán 'Data Mass' và ảnh hưởng của nó lên tốc độ thực tại (Bekenstein Bound).
 */
class InformationDensityEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'information_density';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 69;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $cosmic = $state->getCosmic();
        
        // 1. Tính toán thực thể thông tin (Information Entities)
        $mythCount = count($state->get('meta.active_myths', []));
        $meaningCount = count($state->get('meta.meaning_systems', []));
        $knowledgeCount = count($state->get('meta.knowledge_graph', []));
        
        // 2. Tính toán Causal History (Lịch sử nhân quả)
        $chronicleCount = count($state->get('recentChronicles', []));
        $scarCount = count($state->get('historical_scars', []));
        
        // 3. Tính toán Data Mass (Khối lượng dữ liệu)
        // Hệ số trọng số cho mức độ thâm dụng thông tin
        $dataMass = ($mythCount * 0.02) + 
                    ($meaningCount * 0.05) + 
                    ($knowledgeCount * 0.03) + 
                    ($chronicleCount * 0.01) + 
                    ($scarCount * 0.04);
        
        // Áp dụng giới hạn Bekenstein (Chuẩn hóa về 0-1.5 để cho phép vượt ngưỡng)
        $dataMass = min(1.5, $dataMass);
        $cosmic['data_mass'] = round($dataMass, 4);

        // 4. Time Dilation & Information Crystallization
        if ($dataMass > 0.8) {
            $dilationFactor = ($dataMass - 0.8) * 2.5; // Tăng dần từ 0 -> 1.75
            $cosmic['time_dilation'] = round($dilationFactor, 4);
            
            Log::warning("Terminal Horizon: Universe #{$state->get('universe_id')} density critical.", [
                'data_mass' => $dataMass,
                'dilation' => $dilationFactor
            ]);

            // Entropy tăng mạnh khi thông tin bão hòa
            $fields = $state->getFields();
            if (isset($fields['entropy'])) {
                $fields['entropy'] = min(1.0, $fields['entropy'] + ($dilationFactor * 0.05));
                $state->setFields($fields);
            }

            // Đánh dấu bão hòa cho các Engine khác (Slowing down)
            $cosmic['saturation_lock'] = $dataMass > 0.95;
        } else {
            $cosmic['time_dilation'] = 0;
            $cosmic['saturation_lock'] = false;
        }

        $state->setCosmic($cosmic);

        return new EngineResult([], [], []);
    }
}
