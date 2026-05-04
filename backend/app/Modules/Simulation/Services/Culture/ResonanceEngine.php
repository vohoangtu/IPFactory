<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Simulation\Actions\SynchronizeRealityAction;
use Illuminate\Support\Facades\Log;

class ResonanceEngine
{
    public function __construct(
        protected SynchronizeRealityAction $synchronizeAction
    ) {}

    /**
     * Tính toán cộng hưởng giữa vũ trụ hiện tại và các vũ trụ khác trong cùng Thế giới.
     */
    public function process(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $otherUniverses = Universe::where('world_id', $universe->world_id)
            ->where('id', '!=', $universe->id)
            ->where('status', 'active')
            ->get();

        foreach ($otherUniverses as $other) {
            $resonance = $this->calculateResonance($universe, $other, $snapshot);
            
            if ($resonance > 0.5) {
                $this->updateInteraction($universe, $other, $resonance, $snapshot->tick);
            }
        }
    }

    protected function calculateResonance(Universe $u1, Universe $u2, UniverseSnapshot $s1): float
    {
        if (!$u1->world || !$u2->world) return 0;

        // 1. Đồng bộ Kỷ nguyên (Epoch Synchronicity)
        $e1 = $u1->world->epochs()->where('status', 'active')->first();
        $e2 = $u2->world->epochs()->where('status', 'active')->first(); 
        
        $epochBonus = ($e1 && $e2 && $e1->theme === $e2->theme) ? 0.3 : 0;

        // 2. Tương đồng về Entropy và Trật tự
        $s2 = $u2->latestSnapshot;
        if (!$s2) return 0;

        $v1 = $s1->state_vector ?? [];
        $v2 = $s2->state_vector ?? [];

        $entropyDiff = abs(($v1['entropy'] ?? 0) - ($v2['entropy'] ?? 0));
        $orderDiff = abs(($v1['order'] ?? 0) - ($v2['order'] ?? 0));

        $similarity = 1.0 - (($entropyDiff + $orderDiff) / 2);

        return min(1.0, $similarity + $epochBonus);
    }

    protected function updateInteraction(Universe $u1, Universe $u2, float $resonance, int $tick): void
    {
        $interaction = UniverseInteraction::updateOrCreate(
            [
                'universe_a_id' => min($u1->id, $u2->id),
                'universe_b_id' => max($u1->id, $u2->id),
            ],
            [
                'interaction_type' => 'resonance',
                'resonance_level' => $resonance,
                'synchronicity_score' => $resonance * 1.2, 
                'payload' => [
                    'last_sync_tick' => $tick,
                    'patterns' => ['temporal_alignment', 'entropy_echo']
                ]
            ]
        );

        if ($resonance > 0.8) {
            $this->synchronizeAction->execute($u1, $u2, $resonance, $tick);
        }
    }
}


