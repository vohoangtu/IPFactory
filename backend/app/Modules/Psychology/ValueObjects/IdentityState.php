<?php

namespace App\Modules\Psychology\ValueObjects;

use InvalidArgumentException;

/**
 * IdentityState
 * 
 * Đại diện cho trạng thái bản ngã của Actor.
 * Xử lý sự mâu thuẫn giữa "Hành vi thực tế" và "Vai trò Archetype".
 */
class IdentityState
{
    public function __construct(
        public readonly float $selfWorth,          // [0, 1]: Độ tự tôn. Thấp -> trầm cảm, dễ bị thao túng. Cao -> kiêu ngạo, ít fear.
        public readonly float $roleConflict,       // [0, 1]: Xung đột giữa hành vi thực tế và Archetype mong đợi.
        public readonly float $archetypeAlignment  // [0, 1]: Mức độ tuân thủ Archetype hiện tại.
    ) {
        $this->validate();
    }

    public static function baseline(): self
    {
        return new self(
            selfWorth: 0.5,
            roleConflict: 0.0,
            archetypeAlignment: 1.0
        );
    }

    public function applyDelta(
        float $worthDelta,
        float $conflictDelta,
        float $alignmentDelta
    ): self {
        return new self(
            selfWorth: max(0.0, min(1.0, $this->selfWorth + $worthDelta)),
            roleConflict: max(0.0, min(1.0, $this->roleConflict + $conflictDelta)),
            archetypeAlignment: max(0.0, min(1.0, $this->archetypeAlignment + $alignmentDelta))
        );
    }

    /**
     * Bản ngã có đang trong trạng thái khủng hoảng (Crisis) không?
     * Xảy ra khi mâu thuẫn vai trò quá cao và niềm tin vào bản thân sụp đổ.
     */
    public function isCrisis(): bool
    {
        return $this->roleConflict > 0.8 || $this->selfWorth < 0.2;
    }

    public function toArray(): array
    {
        return [
            'self_worth'          => $this->selfWorth,
            'role_conflict'       => $this->roleConflict,
            'archetype_alignment' => $this->archetypeAlignment,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['self_worth'] ?? 0.5,
            $data['role_conflict'] ?? 0.0,
            $data['archetype_alignment'] ?? 1.0
        );
    }

    private function validate(): void
    {
        if ($this->selfWorth < 0.0 || $this->selfWorth > 1.0) {
            throw new InvalidArgumentException("Self worth must be between 0.0 and 1.0");
        }
        if ($this->roleConflict < 0.0 || $this->roleConflict > 1.0) {
            throw new InvalidArgumentException("Role conflict must be between 0.0 and 1.0");
        }
        if ($this->archetypeAlignment < 0.0 || $this->archetypeAlignment > 1.0) {
            throw new InvalidArgumentException("Archetype alignment must be between 0.0 and 1.0");
        }
    }
}
