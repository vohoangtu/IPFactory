<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Phase 72: Zenith Metrics Service 🛰️📊
 * 
 * Tổng hợp các chỉ số siêu việt cho Zenith Dashboard.
 * Cung cấp cái nhìn toàn cảnh về quá trình tiến hóa của mã nguồn và thực tại.
 */
class ZenithMetricsService
{
    /**
     * Get all high-level metrics from the current world state.
     */
    public function getZenithReport(WorldState $state): array
    {
        $cosmic = $state->getCosmic();
        $fields = $state->getFields();
        
        return [
            'singularity' => [
                'progress' => (float)$state->get('meta.singularity_progress', 0),
                'stability' => (float)$state->get('stability_index', 1.0),
                'entropy' => (float)($fields['entropy'] ?? 0),
                'structural_coherence' => (float)($fields['structural_coherence'] ?? 1.0),
                'transcendence_vector' => $state->get('meta.zenith.singularity.vector', 'PHYSICAL_STASIS'),
                'ascension_active' => (bool)$state->get('meta.zenith_ascension_active', false),
            ],
            'terminal_horizon' => [
                'data_mass' => (float)($cosmic['data_mass'] ?? 0),
                'time_dilation' => (float)($cosmic['time_dilation'] ?? 0),
                'saturation_lock' => (bool)($cosmic['saturation_lock'] ?? false),
            ],
            'autopoiesis' => [
                'active_mutations' => count($state->get('meta.active_mutations', [])),
                'mutation_rate' => (float)$state->get('meta.rule_mutation_rate', 0),
                'logic_complexity' => $this->estimateLogicComplexity($state),
            ],
            'culture_soul' => [
                'active_myths' => count($state->get('meta.active_myths', [])),
                'meaning_systems' => count($state->get('meta.meaning_systems', [])),
                'knowledge_nodes' => count($state->get('meta.knowledge_graph', [])),
                'historical_scars' => count($state->get('historical_scars', [])),
            ],
            'eternal_now' => [
                'time_saliency' => (float)$state->get('meta.time_saliency', 0),
                'last_tick_jump' => (int)$state->get('meta.last_tick_jump', 1),
                'execution_efficiency' => $this->calculateEfficiency($state),
            ],
            'performance' => [
                'ms_per_tick' => (float)$state->get('meta.last_tick_duration_ms', 0),
            ]
        ];
    }

    /**
     * Ước tính độ phức tạp của logic.
     */
    private function estimateLogicComplexity(WorldState $state): float
    {
        $resonance = (float)$state->get('field_resonance', 0);
        $knowledge = (float)$state->get('civilization.knowledge', 0);
        
        return min(1.0, ($resonance * 0.4) + ($knowledge * 0.6));
    }

    /**
     * Tính toán hiệu suất thời gian (Tickless efficiency).
     */
    private function calculateEfficiency(WorldState $state): float
    {
        $jump = (int)$state->get('meta.last_tick_jump', 1);
        if ($jump <= 1) return 1.0;
        
        return round($jump, 2);
    }
}

