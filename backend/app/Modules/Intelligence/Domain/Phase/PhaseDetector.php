<?php

namespace App\Modules\Intelligence\Domain\Phase;

class PhaseDetector
{
    /**
     * Detects the macro-phase of the civilization continuously.
     * @param float $culturalMomentum Gia tốc văn hóa (Phase 25)
     * @param array $historicalFlags Dấu ấn lịch sử (Phase 33)
     */
    public function detect(float $entropy, float $polarization, float $techLevel, float $culturalMomentum = 0.0, array $historicalFlags = []): PhaseScore
    {
        // Momentum cao giúp "phá vỡ" rào cản tech nhanh hơn
        $effectiveTech = $techLevel * (1.0 + $culturalMomentum);

        $fragmented = $entropy * $polarization;
        $information = $this->sigmoid($effectiveTech - 6) * (1 - $entropy) * (1 - $polarization);
        $industrial = $this->sigmoid($effectiveTech - 3) * (1 - $entropy);
        $feudal = $this->sigmoid($effectiveTech - 1) * (1 - $information) * (1 - $industrial);
        $primitive = 1 - max($fragmented, $information, $industrial, $feudal);

        // Phase 33: Hysteresis (regression prevention)
        if (!empty($historicalFlags['industrialized'])) {
            $primitive *= 0.2; // Rất khó quay về Primitive nếu đã Industrialized
        }
        if (!empty($historicalFlags['information_age'])) {
            $primitive *= 0.1;
            $feudal *= 0.3;
        }

        // Normalize to prevent negative values from edge cases
        return new PhaseScore(
            primitive: max(0, $primitive),
            feudal: max(0, $feudal),
            industrial: max(0, $industrial),
            information: max(0, $information),
            fragmented: max(0, $fragmented)
        );
    }

    private function sigmoid(float $x): float
    {
        return 1.0 / (1.0 + exp(-$x));
    }
}
