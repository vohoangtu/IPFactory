<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Intelligence\Models\Actor;

/**
 * CapabilityEngine — Phase 1.
 * Computes capability vector (intellect, charisma, wealth, followers, authority, creativity)
 * from actor traits, age, and optional context. Results 0–1, stored in actor.capabilities.
 */
class CapabilityEngine
{
    private const KEYS = ['intellect', 'charisma', 'wealth', 'followers', 'authority', 'creativity'];

    public function compute(Actor $actor, int $currentTick): array
    {
        $traits = $actor->traits ?? [];
        $metrics = $actor->metrics ?? [];
        $config = config('worldos.capability', []);

        $out = [];
        foreach (self::KEYS as $key) {
            $formula = $config[$key] ?? [];
            if (is_array($formula) && ! empty($formula)) {
                $sum = 0.0;
                foreach ($formula as $traitIndex => $weight) {
                    $v = (float) ($traits[$traitIndex] ?? 0.5);
                    $sum += $v * (float) $weight;
                }
                $out[$key] = round((float) min(1.0, max(0.0, $sum)), 4);
            } else {
                // Fallback from metrics or default
                $out[$key] = match ($key) {
                    'wealth' => (float) min(1.0, max(0.0, ($metrics['wealth'] ?? 0) / 100)),
                    'followers' => (float) min(1.0, max(0.0, ($metrics['followers'] ?? 0) / 100)),
                    'authority' => (float) min(1.0, max(0.0, ($metrics['influence'] ?? 0) / 100)),
                    default => 0.5,
                };
            }
        }

        return $out;
    }

    /**
     * Compute and persist capabilities on the actor.
     */
    public function computeAndStore(Actor $actor, int $currentTick): array
    {
        $capabilities = $this->compute($actor, $currentTick);
        $actor->capabilities = $capabilities;
        $actor->save();

        return $capabilities;
    }
}

