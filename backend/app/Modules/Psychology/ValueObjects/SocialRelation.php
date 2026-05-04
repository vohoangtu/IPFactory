<?php

namespace App\Modules\Psychology\ValueObjects;

use InvalidArgumentException;

/**
 * SocialRelation
 * 
 * Đại diện cho một cạnh trong đồ thị quan hệ xã hội thưa (sparse social graph).
 * Chỉ lưu trữ cảm nhận 1 chiều (subjective) từ Actor A -> Target Actor B.
 */
class SocialRelation
{
    public function __construct(
        public readonly int $targetId,
        public readonly float $trust,               // [-1, 1]: -1 = thù hận, 1 = tin tưởng tuyệt đối
        public readonly float $fear,                // [0, 1]:  1 = vô cùng khiếp sợ
        public readonly float $dominancePerceived,  // [-1, 1]: -1 = target thống trị mình, 1 = mình thống trị target
        public readonly float $intimacy,            // [0, 1]:  Mức độ gần gũi/chia sẻ (0 = người lạ, 1 = tri kỷ)
        public readonly int $lastInteractionTick    // Tick cuối cùng có tương tác (dùng cho decay)
    ) {
        $this->validate();
    }

    public static function neutral(int $targetId, int $tick = 0): self
    {
        return new self(
            targetId: $targetId,
            trust: 0.0,
            fear: 0.0,
            dominancePerceived: 0.0,
            intimacy: 0.0,
            lastInteractionTick: $tick
        );
    }

    public function applyDelta(
        float $trustDelta,
        float $fearDelta,
        float $dominanceDelta,
        float $intimacyDelta,
        int $currentTick
    ): self {
        return new self(
            targetId: $this->targetId,
            trust: max(-1.0, min(1.0, $this->trust + $trustDelta)),
            fear: max(0.0, min(1.0, $this->fear + $fearDelta)),
            dominancePerceived: max(-1.0, min(1.0, $this->dominancePerceived + $dominanceDelta)),
            intimacy: max(0.0, min(1.0, $this->intimacy + $intimacyDelta)),
            lastInteractionTick: $currentTick
        );
    }

    /**
     * Phai mờ (decay) quan hệ theo thời gian.
     * Cảm xúc mãnh liệt (fear, hatred) phai mờ chậm hơn intimacy/trust.
     */
    public function decay(int $currentTick, float $decayRatePerTick = 0.01): self
    {
        $ticksElapsed = $currentTick - $this->lastInteractionTick;
        if ($ticksElapsed <= 0) {
            return $this;
        }

        $totalDecay = 1.0 - pow(1.0 - $decayRatePerTick, $ticksElapsed);

        return new self(
            targetId: $this->targetId,
            // Trust tiến về 0
            trust: $this->trust * (1.0 - $totalDecay),
            // Fear tiến về 0 (nhưng chậm hơn trust)
            fear: $this->fear * (1.0 - ($totalDecay * 0.5)),
            // Dominance tiến về 0
            dominancePerceived: $this->dominancePerceived * (1.0 - $totalDecay),
            // Intimacy tiến về 0 nhanh hơn
            intimacy: $this->intimacy * (1.0 - ($totalDecay * 1.5)),
            lastInteractionTick: $this->lastInteractionTick // KHÔNG update tick khi decay
        );
    }

    /**
     * Độ mạnh (intensity) tổng hợp của mối quan hệ này.
     * Dùng để filter lọc ra những mối quan hệ đáng nhớ nhất (Dunbar's number).
     */
    public function getIntensity(): float
    {
        return max(
            abs($this->trust),
            $this->fear,
            $this->intimacy,
            abs($this->dominancePerceived)
        );
    }

    public function toArray(): array
    {
        return [
            'target_id'           => $this->targetId,
            'trust'               => $this->trust,
            'fear'                => $this->fear,
            'dominance_perceived' => $this->dominancePerceived,
            'intimacy'            => $this->intimacy,
            'last_interaction'    => $this->lastInteractionTick,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['target_id'] ?? 0,
            $data['trust'] ?? 0.0,
            $data['fear'] ?? 0.0,
            $data['dominance_perceived'] ?? 0.0,
            $data['intimacy'] ?? 0.0,
            $data['last_interaction'] ?? 0
        );
    }

    private function validate(): void
    {
        if ($this->trust < -1.0 || $this->trust > 1.0) {
            throw new InvalidArgumentException("Trust must be between -1.0 and 1.0");
        }
        if ($this->fear < 0.0 || $this->fear > 1.0) {
            throw new InvalidArgumentException("Fear must be between 0.0 and 1.0");
        }
        if ($this->dominancePerceived < -1.0 || $this->dominancePerceived > 1.0) {
            throw new InvalidArgumentException("Dominance must be between -1.0 and 1.0");
        }
        if ($this->intimacy < 0.0 || $this->intimacy > 1.0) {
            throw new InvalidArgumentException("Intimacy must be between 0.0 and 1.0");
        }
    }
}
