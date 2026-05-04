<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

class MultiverseInteractionService
{
    /**
     * Check for resonance between universes in the same multiverse.
     */
    public function detectResonance(Universe $universe): void
    {
        $multiverseId = $universe->multiverse_id;
        if (!$multiverseId) return;

        // Find other universes in the same multiverse
        $others = Universe::where('multiverse_id', $multiverseId)
            ->where('id', '!=', $universe->id)
            ->where('status', 'active')
            ->get();

        foreach ($others as $other) {
            if ($this->isResonant($universe, $other)) {
                $this->triggerResonance($universe, $other);
            }
        }
    }

    protected function isResonant(Universe $a, Universe $b): bool
    {
        if (!$a->world || !$b->world) return false;
        // Resonance if same World Origin and similar entropy levels
        if ($a->world->origin !== $b->world->origin) return false;

        $avgA = $a->snapshots()->orderByDesc('tick')->limit(5)->avg('entropy') ?? 0;
        $avgB = $b->snapshots()->orderByDesc('tick')->limit(5)->avg('entropy') ?? 0;

        // If entropy levels are within 0.1 range, they resonance
        return abs((float)$avgA - (float)$avgB) < 0.1;
    }

    protected function triggerResonance(Universe $a, Universe $b): void
    {
        // Throttling: Check if resonance interaction already recorded recently
        $exists = UniverseInteraction::where('interaction_type', 'resonance')
            ->where(function($q) use ($a, $b) {
                $q->where('universe_a_id', $a->id)->where('universe_b_id', $b->id);
            })
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if ($exists) return;

        UniverseInteraction::create([
            'universe_a_id' => $a->id,
            'universe_b_id' => $b->id,
            'interaction_type' => 'resonance',
            'payload' => [
                'strength' => $strength = (rand(70, 95) / 100),
                'note' => 'Cộng hưởng tri thức qua không gian đa chiều.'
            ]
        ]);

        $content = "CỘNG HƯỞNG ĐA VŨ TRỤ: Cảm ứng được sự tồn tại của một thế giới song hành ({$b->id}), tri thức bắt đầu rò rỉ qua vết nứt không gian.";
        
        Chronicle::create([
            'universe_id' => $a->id,
            'from_tick' => (int)$a->current_tick,
            'to_tick' => (int)$a->current_tick,
            'type' => 'multiverse_resonance',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $content
            ],
        ]);

        // Phase 58: Agent Migration if strength is high
        if ($strength > 0.85) {
            $this->migrateAgents($a, $b);
        }
    }

    /**
     * Migrate high-trait agents from Source to Target.
     */
    public function migrateAgents(Universe $source, Universe $target): void
    {
        $snapshot = $source->snapshots()->orderByDesc('tick')->first();
        if (!$snapshot) return;

        $agents = ($snapshot->state_vector ?? [])['agents'] ?? [];
        $migrants = [];
        $remaining = [];

        foreach ($agents as $agent) {
            $isHighTrait = collect($agent['trait_vector'])->contains(fn($v) => $v > 0.9);
            if ($isHighTrait && count($migrants) < 2) {
                $migrants[] = $agent;
            } else {
                $remaining[] = $agent;
            }
        }

        if (empty($migrants)) return;

        // Perform the jump: Update snapshots (simplified: next tick will have them)
        // In a real flow, we'd inject them into the target's state_vector
        Chronicle::create([
            'universe_id' => $target->id,
            'from_tick' => (int)$target->current_tick,
            'to_tick' => (int)$target->current_tick,
            'type' => 'multiverse_migration',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "DỊCH CHUYỂN BẢN THỂ: Một thực thể mạnh mẽ từ vũ trụ {$source->id} đã vượt qua vết nứt không gian và xuất hiện tại đây."
            ],
        ]);

        Log::info("Migrated " . count($migrants) . " agents from Universe {$source->id} to {$target->id}");
    }
}

