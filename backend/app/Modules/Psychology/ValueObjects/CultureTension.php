<?php

namespace App\Modules\Psychology\ValueObjects;

use InvalidArgumentException;

/**
 * CultureTension
 * 
 * Thể hiện mức độ "căng thẳng" hoặc xung đột ý thức hệ của một Zone/Culture.
 * Có thể coi đây là "tâm lý tập thể" (aggregate psychology).
 * Dùng để tạo ra Mức áp lực đồng trang lứa (Peer Pressure).
 */
class CultureTension
{
    public function __construct(
        public readonly float $conservativeVsProgressive,   // [-1, 1]: -1 cực đoan truyền thống, 1 cực đoan cách tân
        public readonly float $collectivismVsIndividualism, // [-1, 1]: -1 tập thể/đàn áp cái tôi, 1 đề cao cá nhân tự do
        public readonly float $peaceVsAggression,           // [-1, 1]: -1 yêu chuộng hòa bình, 1 cuồng chiến/xâm lược
        public readonly float $cohesion                     // [0, 1]: Mức độ gắn kết xã hội (0: rệu rã, 1: đoàn kết tuyệt đối)
    ) {
        $this->validate();
    }

    public static function neutral(): self
    {
        return new self(0.0, 0.0, 0.0, 0.5); // Cohesion base 0.5
    }

    public function applyDelta(
        float $conservativeDelta,
        float $collectivismDelta,
        float $aggressionDelta,
        float $cohesionDelta
    ): self {
        return new self(
            conservativeVsProgressive: max(-1.0, min(1.0, $this->conservativeVsProgressive + $conservativeDelta)),
            collectivismVsIndividualism: max(-1.0, min(1.0, $this->collectivismVsIndividualism + $collectivismDelta)),
            peaceVsAggression: max(-1.0, min(1.0, $this->peaceVsAggression + $aggressionDelta)),
            cohesion: max(0.0, min(1.0, $this->cohesion + $cohesionDelta))
        );
    }

    /**
     * Tính toán Pressure Threshold (Áp lực).
     * Bầu không khí càng đoàn kết (cohesion cao) và các cán cân lệch 1 hướng càng nhiều,
     * mức áp lực "bắt buộc tuân thủ văn hoá" dành cho kẻ dị biệt càng lớn.
     */
    public function computePeerPressureIntensity(): float
    {
        $magnitude = max(
            abs($this->conservativeVsProgressive),
            abs($this->collectivismVsIndividualism),
            abs($this->peaceVsAggression)
        );

        return $magnitude * $this->cohesion;
    }

    public function toArray(): array
    {
        return [
            'conservative_vs_progressive'   => $this->conservativeVsProgressive,
            'collectivism_vs_individualism' => $this->collectivismVsIndividualism,
            'peace_vs_aggression'           => $this->peaceVsAggression,
            'cohesion'                      => $this->cohesion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['conservative_vs_progressive'] ?? 0.0,
            $data['collectivism_vs_individualism'] ?? 0.0,
            $data['peace_vs_aggression'] ?? 0.0,
            $data['cohesion'] ?? 0.5
        );
    }

    private function validate(): void
    {
        if ($this->conservativeVsProgressive < -1.0 || $this->conservativeVsProgressive > 1.0) {
            throw new InvalidArgumentException("Conservative vs Progressive must be [-1.0, 1.0]");
        }
        if ($this->collectivismVsIndividualism < -1.0 || $this->collectivismVsIndividualism > 1.0) {
            throw new InvalidArgumentException("Collectivism vs Individualism must be [-1.0, 1.0]");
        }
        if ($this->peaceVsAggression < -1.0 || $this->peaceVsAggression > 1.0) {
            throw new InvalidArgumentException("Peace vs Aggression must be [-1.0, 1.0]");
        }
        if ($this->cohesion < 0.0 || $this->cohesion > 1.0) {
            throw new InvalidArgumentException("Cohesion must be [0.0, 1.0]");
        }
    }
}
