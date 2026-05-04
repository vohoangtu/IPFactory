<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\NarrativeFeedbackSignal;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeFeedbackService: Manages the injection of narrative-driven intent back into the simulation.
 */
class NarrativeFeedbackService
{
    /**
     * Queue a new influence signal for the simulation.
     */
    public function queueSignal(int $universeId, int $applyAtTick, string $type, array $payload): NarrativeFeedbackSignal
    {
        Log::info("NarrativeFeedbackService: Queuing {$type} signal for universe {$universeId} at tick {$applyAtTick}");

        return NarrativeFeedbackSignal::create([
            'universe_id' => $universeId,
            'apply_at_tick' => $applyAtTick,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending'
        ]);
    }

    /**
     * Helper to create a 'Dark Attractor' signal (High entropy influence).
     */
    public function createCrisis(int $universeId, int $targetTick, float $intensity = 0.5): NarrativeFeedbackSignal
    {
        return $this->queueSignal($universeId, $targetTick, 'dark_attractor', [
            'entropy_threshold' => 0.7,
            'pull_strength' => $intensity,
            'collapse_probability' => $intensity * 0.2
        ]);
    }

    /**
     * Helper to create an 'Emotion Spike' signal (Collective psychology influence).
     */
    /**
     * Map LLM-suggested omens to simulation signals.
     */
    public function applyOmens($universe, array $omens): void
    {
        $universeId = is_array($universe) ? $universe['id'] : $universe->id;
        $nextTick = (is_array($universe) ? $universe['tick'] : ($universe->tick ?? 0)) + 1;

        foreach ($omens as $omen) {
            $type = match ($omen['type'] ?? '') {
                'crisis' => 'dark_attractor',
                'oracle' => 'emotion_spike',
                'mutation' => 'material_mutation',
                default => 'omen'
            };

            $payload = [
                'description' => $omen['description'] ?? 'Unnamed omen',
                'intensity' => $omen['intensity'] ?? 0.5,
                // Additional logic to derive parameters from description could go here
            ];

            // If it's a crisis, use the specialized helper logic
            if ($type === 'dark_attractor') {
                $this->createCrisis($universeId, $nextTick, $payload['intensity']);
            } else {
                $this->queueSignal($universeId, $nextTick, $type, $payload);
            }
        }
    }
}
