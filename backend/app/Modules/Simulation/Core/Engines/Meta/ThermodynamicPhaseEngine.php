<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 71: Thermodynamic Phase Engine 🌡️🌀
 * 
 * Mô phỏng sự chuyển pha xã hội dựa trên Entropy và Dòng chảy năng lượng.
 * Thay thế CivilizationPhaseTransitionEngine cổ điển bằng hệ thống chuyển pha vật lý.
 * SOLID -> LIQUID -> GAS -> PLASMA.
 */
class ThermodynamicPhaseEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'thermodynamic_phase';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 71;
    }

    public function tickRate(): int
    {
        return 50; // Chạy thưa hơn (mỗi 50 ticks) vì chuyển pha là sự kiện vĩ mô
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $currentPhase = $state->get('meta.civilization_phase', 'SOLID');
        
        $energy = (float) $state->get('net_energy', 0);
        $entropy = (float) $state->get('entropy', 0);
        
        $economy = $state->get('civilization.economy', []);
        $tradeFlow = (float) ($economy['trade_volume'] ?? 0);
        
        $knowledge = (float) $state->get('fields.knowledge', 0);
        
        $nextPhase = $currentPhase;
        $rulesToInject = [];

        // Logic chuyển pha thermodynamic
        switch ($currentPhase) {
            case 'SOLID':
                // Chuyển sang LIQUID khi thặng dư năng lượng và bắt đầu có giao thương
                // SOLID: Local, static, resource-bound.
                if ($energy > 0.4 && $tradeFlow > 50.0) { 
                    $nextPhase = 'LIQUID';
                    $rulesToInject = [
                        'energy_efficiency' => 0.5, 
                        'migration_cost_multiplier' => 0.8,
                        'trade_friction' => 0.9
                    ];
                }
                break;

            case 'LIQUID':
                // Chuyển sang GAS khi tri thức bùng nổ và thông tin lan tỏa cực nhanh
                // LIQUID: Expansive, empire-forming, flowing.
                if ($knowledge > 0.6 && $tradeFlow > 200.0) {
                    $nextPhase = 'GAS';
                    $rulesToInject = [
                        'energy_efficiency' => 0.7, 
                        'information_density_bonus' => 1.2,
                        'entropy_decay_rate' => 0.08
                    ];
                }
                // Có thể "đóng băng" lại thành SOLID nếu năng lượng âm quá nặng (Dark Age)
                if ($energy < -0.5) {
                    $nextPhase = 'SOLID';
                }
                break;

            case 'GAS':
                // Chuyển sang PLASMA (Singularity) khi tri thức cực đại và entropy bắt đầu mất kiểm soát
                // GAS: Global, chaotic, ultra-innovative.
                if ($knowledge > 0.9 && $entropy > 0.7) {
                    $nextPhase = 'PLASMA';
                    $rulesToInject = [
                        'mystic_constant' => 0.4, 
                        'reality_ancients_resurrect' => true,
                        'energy_efficiency' => 1.0
                    ];
                }
                // Điềm tĩnh lại thành LIQUID nếu entropy được dập tắt (Stagnation)
                if ($entropy < 0.1) {
                    $nextPhase = 'LIQUID';
                }
                break;
        }

        if ($nextPhase !== $currentPhase) {
            $state->set('meta.civilization_phase', $nextPhase);
            Log::alert("THERMODYNAMIC PHASE SHIFT: Universe {$ctx->getUniverseId()} transitioned from {$currentPhase} to {$nextPhase}!", [
                'energy' => $energy,
                'trade' => $tradeFlow,
                'knowledge' => $knowledge
            ]);
            
            // Chích (Inject) Axiom mới vào Vũ trụ
            $this->injectAxioms($ctx->getUniverseId(), $rulesToInject);
            
            // Tác động nhiễu loạn State (Entropy spike)
            $state->set('entropy', $entropy + 0.15);
        }

        return new EngineResult([], [], []);
    }

    private function injectAxioms(int $universeId, array $rules): void
    {
        if (empty($rules)) return;

        $universe = \App\Modules\World\Models\Universe::find($universeId);
        if ($universe) {
            $axioms = $universe->axioms ?? [];
            $universe->axioms = array_merge($axioms, $rules);
            $universe->save();
            Log::info("Thermodynamic Phase Engine: Injected new Axioms to Universe {$universeId}", $rules);
        }
    }
}

