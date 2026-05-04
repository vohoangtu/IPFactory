<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Demiurge;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * FaithService: Manages the divine alignment of agents and legends (§V16).
 * Alignments shift based on TraitVetor orientation.
 */
class FaithService
{
    /**
     * Calculate and update the alignment of a legendary agent.
     */
    public function updateAlignment(LegendaryAgent $legend, array $traits): void
    {
        $order = (float)($traits['order'] ?? 0.5);
        $entropy = (float)($traits['entropy'] ?? 0.5);
        
        $newAlignmentId = $this->determineAlignment($order, $entropy);

        if ($legend->alignment_id !== $newAlignmentId) {
            $this->applyAlignmentShift($legend, $newAlignmentId);
        }
    }

    protected function determineAlignment(float $order, float $entropy): ?int
    {
        // Simple heuristic mapping traits to Demiurge intentions
        if ($order > 0.75) {
             return Demiurge::where('intention_type', 'order')->first()?->id;
        }
        
        if ($entropy > 0.75) {
             return Demiurge::where('intention_type', 'chaos')->first()?->id;
        }

        if ($order > 0.4 && $entropy > 0.4) {
             return Demiurge::where('intention_type', 'sovereignty')->first()?->id;
        }

        return null; // Neutral / Unaligned
    }

    protected function applyAlignmentShift(LegendaryAgent $legend, ?int $newAlignmentId): void
    {
        $oldAlignmentId = $legend->alignment_id;
        $legend->update(['alignment_id' => $newAlignmentId]);

        $newDemiurge = $newAlignmentId ? Demiurge::find($newAlignmentId) : null;
        $newName = $newDemiurge ? $newDemiurge->name : "The Unaligned Paths";

        Log::info("FAITH: Legend #{$legend->id} has shifted alignment to {$newName}");

        Chronicle::create([
            'universe_id' => $legend->universe_id,
            'from_tick' => $legend->universe->current_tick,
            'to_tick' => $legend->universe->current_tick,
            'type' => 'divine_alignment',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "LỜI THỀ ĐỨC TIN: Legend [{$legend->name}] đã tuyên thệ trung thành với ý chí của {$newName}."
            ],
        ]);
    }
}

