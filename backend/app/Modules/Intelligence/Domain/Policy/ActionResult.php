<?php

namespace App\Modules\Intelligence\Domain\Policy;

/**
 * Value Object returned by every AgentAction implementation.
 * Contains all side-effects in data form — none are applied directly by the action.
 */
final class ActionResult
{
    public function __construct(
        /** A biography entry to append to the actor, or null if none. */
        public readonly ?string $biographyEntry,

        /**
         * Deltas to apply to universe state_vector.
         * e.g. ['entropy' => +0.05, 'stability_index' => -0.05]
         */
        public readonly array $universeImpact,

        /**
         * Optional chronicle raw_payload to persist.
         * Null means no chronicle entry for this action.
         */
        public readonly ?array $chronicleEntry,
    ) {}

    public function hasChronicle(): bool
    {
        return $this->chronicleEntry !== null;
    }

    public function hasBiography(): bool
    {
        return $this->biographyEntry !== null;
    }
}
