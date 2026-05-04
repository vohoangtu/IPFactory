<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Intelligence\Services\AI\MemoryService;
use App\Modules\Simulation\Events\SimulationEventOccurred;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Handle History Resonance: Current events interact with the ghosts of the past.
 * This listener autonomously adjusts the reality state based on historical resonance.
 */
class ApplyMemoryResonance
{
    public function __construct(
        protected MemoryService $memoryService
    ) {}

    public function handle(SimulationEventOccurred $event): void
    {
        $content = $event->payload['description'] ?? "{$event->type} occurred at tick {$event->tick}";
        
        // Find memories that resonate with this event
        $resonances = $this->memoryService->findResonance($content, $event->universeId);
        
        if (empty($resonances)) {
            return;
        }

        $totalImpact = 0.0;
        foreach ($resonances as $res) {
            // Resonance Impact (I_r) = Similarity * (Importance / 10) * Temporal_Decay
            $temporalDecay = $this->calculateTemporalDecay((int)$event->tick, (int)$res['original_tick']);
            $impact = $res['score'] * ($res['importance'] / 10.0) * $temporalDecay;
            
            $totalImpact += $impact;

            Log::info("Resonance detected in Universe {$event->universeId}: Current '{$event->type}' vs Past Memory '{$res['id']}' (Score: {$res['score']})");
        }

        // Apply autonomous back-pressure to the universe
        if ($totalImpact > 0.1) {
            $this->applyCausalPressure($event->universeId, $totalImpact);
        }
    }

    /**
     * T8 - Hữu hạn: Dữ liệu quá cũ có tác động yếu hơn.
     */
    protected function calculateTemporalDecay(int $currentTick, int $pastTick): float
    {
        $age = max(1, $currentTick - $pastTick);
        // Harmonic decay: 1 / log(10 + age)
        return 1.0 / log10(10 + $age);
    }

    /**
     * Inject entropy/pressure back into the universe state autonomously.
     */
    protected function applyCausalPressure(int $universeId, float $impact): void
    {
        $universe = Universe::find($universeId);
        if (!$universe) return;

        $vec = $universe->state_vector ?? [];
        
        // Impact scale: 0.1 to 0.5
        $entropyInjected = min(0.1, $impact * 0.05);
        
        $vec['entropy'] = min(1.0, ($vec['entropy'] ?? 0.0) + $entropyInjected);
        $vec['resonance_pressure'] = ($vec['resonance_pressure'] ?? 0.0) + $impact;
        
        // If resonance is very high, trigger a "Ghost Echo" anomaly
        if ($impact > 0.8) {
            $vec['active_crises']['ghost_echo'] = [
                'start_tick' => $universe->current_tick,
                'intensity' => $impact
            ];
        }

        $universe->update(['state_vector' => $vec]);
        
        Log::info("Autonomous Causal Pressure applied to Universe {$universeId}: +{$entropyInjected} Entropy due to Historical Resonance.");
    }
}
