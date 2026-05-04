<?php

namespace App\Modules\Intelligence\Domain\Policy;

/**
 * Immutable Value Object returned by narrative-loom /actor-intent.
 * Carries LLM-generated decision data.
 */
final class IntentResponse
{
    public function __construct(
        public readonly string  $action,
        public readonly float   $intensity,
        public readonly ?string $target,
        /** The LLM-generated explanation — written directly into actor biography. */
        public readonly string  $reasoning,
        public readonly float   $confidence,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            action:     $data['action'],
            intensity:  (float) ($data['intensity'] ?? 0.5),
            target:     $data['target'] ?? null,
            reasoning:  $data['reasoning'] ?? '',
            confidence: (float) ($data['confidence'] ?? 0.7),
        );
    }

    /** True if LLM is confident enough to override DecisionEngine. */
    public function isReliable(): bool
    {
        return $this->confidence >= 0.5;
    }
}
