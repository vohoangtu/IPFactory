<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Phase 59: Advanced Observer Spectrum Service (V8+) 👁️🌈
 * 
 * Phân loại và tính toán ảnh hưởng của các dải quang phổ ý thức lên thực tại.
 */
class ObserverSpectrumService
{
    public const OBSERVER_DEMIURGE = 'demiurge';     // Người chơi / Admin
    public const OBSERVER_HEROIC = 'heroic';         // Anh hùng (17D Actors)
    public const OBSERVER_COLLECTIVE = 'collective'; // Ý thức tập thể (Civilization Resonance)

    /**
     * Tính toán bảng phân bổ ảnh hưởng của các loại quan sát viên.
     */
    public function getSpectrum(WorldState $state): array
    {
        $spectrum = [
            self::OBSERVER_DEMIURGE => 0.0,
            self::OBSERVER_HEROIC => 0.0,
            self::OBSERVER_COLLECTIVE => 0.0
        ];

        // 1. Demiurge Influence (Base observation load from DB/Player)
        $spectrum[self::OBSERVER_DEMIURGE] = (float)$state->get('meta.observation_load', 0.0);

        // 2. Heroic Influence (Sum of heroic actors influence)
        $actors = $state->getActorEntities();
        foreach ($actors as $actor) {
            if ($actor->isHeroic && $actor->isAlive) {
                $spectrum[self::OBSERVER_HEROIC] += (float)($actor->metrics['influence'] ?? 1.0) * 0.5;
            }
        }

        // 3. Collective Influence (Based on Resonance field)
        $fields = $state->getFields();
        $resonance = (float)($fields['resonance'] ?? 0.0);
        $spectrum[self::OBSERVER_COLLECTIVE] = $resonance * 5.0; // Tác động mạnh khi văn minh cộng hưởng cao

        return $spectrum;
    }

    /**
     * Tính toán "Dấu vân tay" (Signature) của thực tại đang bị quan sát.
     * Trả về các modifier cho Entropy và Stability.
     */
    public function getInterferenceSignature(array $spectrum): array
    {
        $totalWeight = array_sum($spectrum);
        if ($totalWeight <= 0) return ['entropy_mod' => 0.0, 'stability_mod' => 0.0, 'total_load' => 0.0];

        // Demiurge: High interference, increases entropy (uncertainty principle)
        // Heroic: Local stabilization, decreases entropy, increases stability
        // Collective: High coherence, greatly increases stability
        
        $entropyMod = ($spectrum[self::OBSERVER_DEMIURGE] * 0.1) - ($spectrum[self::OBSERVER_HEROIC] * 0.05);
        $stabilityMod = ($spectrum[self::OBSERVER_HEROIC] * 0.1) + ($spectrum[self::OBSERVER_COLLECTIVE] * 0.2);

        return [
            'entropy_mod' => $entropyMod,
            'stability_mod' => $stabilityMod,
            'total_load' => $totalWeight
        ];
    }
}


