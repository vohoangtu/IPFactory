<?php

namespace App\Modules\Intelligence\Domain\Macro;

/**
 * MacroPressure — Tính áp lực macro từ phân bố archetype (PHI TUYẾN).
 * 
 * Theo V22 Masterplan §5:
 * - warPressure = warrior_ratio^1.5
 * - knowledgePressure = scholar_ratio * 0.8
 * - tradePressure = merchant_ratio * 0.7
 * - chaosPressure = polarization_index * warlord_ratio
 */
final class MacroPressure
{
    public function __construct(
        public readonly float $warPressure,
        public readonly float $knowledgePressure,
        public readonly float $tradePressure,
        public readonly float $chaosPressure,
        public readonly float $leaderPressure
    ) {}

    /**
     * Tính MacroPressure từ archetype ratios và polarization.
     *
     * @param array<string, float> $archetypeRatios  e.g. ['Chiến Binh' => 0.3, 'Học Giả' => 0.2]
     * @param float $polarizationIndex  Chỉ số phân cực [0, 1]
     */
    public static function fromRatios(array $archetypeRatios, float $polarizationIndex): self
    {
        $warrior = $archetypeRatios['Chiến Binh'] ?? 0.0;
        $scholar = $archetypeRatios['Học Giả'] ?? 0.0;
        $merchant = $archetypeRatios['Thương Nhân'] ?? ($archetypeRatios['Kỹ Sư'] ?? 0.0);
        $warlord = $archetypeRatios['Lãnh Đạo'] ?? 0.0;
        $leader = $archetypeRatios['Lãnh Đạo'] ?? 0.0;

        return new self(
            warPressure: pow($warrior, 1.5),                       // Phi tuyến!
            knowledgePressure: $scholar * 0.8,
            tradePressure: $merchant * 0.7,
            chaosPressure: $polarizationIndex * $warlord,
            leaderPressure: $leader * 0.6
        );
    }

    /**
     * Tính net effect lên entropy/tech/stability.
     * 
     * @return array{entropy_delta: float, tech_delta: float, stability_delta: float}
     */
    public function computeDeltas(float $currentEntropy, float $currentTechLevel): array
    {
        // Entropy: war increases, self-damping (logistic)
        $entropyDelta = $this->warPressure * 0.02
            - $currentEntropy * (1.0 - $currentEntropy) * 0.05  // Logistic damping
            + $this->chaosPressure * 0.01;

        // Tech: knowledge increases with logistic cap
        $techDelta = $this->knowledgePressure * (1.0 - $currentTechLevel / 10.0);

        // Stability: leader increases, war decreases
        $stabilityDelta = $this->leaderPressure * 0.01
            - $this->warPressure * 0.015
            - $this->chaosPressure * 0.005;

        return [
            'entropy_delta' => round($entropyDelta, 8),
            'tech_delta' => round(max(0, $techDelta), 8),
            'stability_delta' => round($stabilityDelta, 8),
        ];
    }
}
