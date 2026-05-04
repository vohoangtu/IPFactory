<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function resource_path;
use function file_get_contents;
use function app;
use function count;
use function is_array;
use Illuminate\Support\Facades\Log;

/**
 * Phase 48: Innovation-Stagnation Engine (Sáng tạo hủy diệt) 📉🚀
 * 
 * Mô hình hóa mâu thuẫn giữa đổi mới và sức ì thể chế.
 * Giải thích tại sao văn minh đạt đỉnh rồi đình trệ.
 */
class InnovationEngine
{
    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $ruleVm ?? app(RuleVmService::class);
    }
    /**
     * Run the engine using the standardized WorldState DTO.
     */
    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $path = 'innovation/collective'; // RuleVmService loads from resource_path('worldos_rules/')

        // Evaluation directly against the manifold
        $this->ruleVm->evaluateAndApplyWithDsl($state, $path, $tick);
        
        Log::debug("InnovationEngine: Evaluated innovation dynamics for Universe {$state->get('universe_id')} at tick {$tick}");
    }

    public function step(Universe $universe): void
    {
        // This method will be deprecated soon in favor of runWithState
        // For now, we delegate if we have a state available, or we just do nothing
        // as the Pipeline will handle it.
    }

    private function applyCrisisSideEffects(Universe $universe, array &$metrics): void
    {
        Log::warning("STAGNATION CRISIS: Universe #{$universe->id} has reached a breaking point. Institutional collapse imminent.");
        
        $stateVector = $universe->state_vector;
        if (isset($stateVector['fields']) && is_array($stateVector['fields'])) {
            foreach ($stateVector['fields'] as $key => &$val) {
                $val *= 0.7; // Crisis hurts!
            }
        }
        
        // Add a historical scar
        $stateVector['historical_scars'][] = [
            'tick' => $universe->current_tick ?? 0,
            'type' => 'STAGNATION_REVOLUTION',
            'description' => "Một cuộc cách mạng/khủng hoảng nổ ra do sự đình trệ quá mức, phá vỡ xiềng xích cũ."
        ];
        
        $universe->state_vector = $stateVector;
    }

    private function getAverageCuriosity(Universe $universe): float
    {
        // Simple mock: based on knowledge field
        return ($universe->state_vector['fields']['knowledge'] ?? 0.1) * 0.5 + 0.3;
    }

    /**
     * Lấy hệ số điều chỉnh hiệu suất cho các hành động (Research, Growth).
     */
    public function getInnovationModifier(array $metrics): float
    {
        $stagnation = $metrics['stagnation_score'] ?? 0.0;
        return max(0.1, 1.0 - $stagnation);
    }
}





