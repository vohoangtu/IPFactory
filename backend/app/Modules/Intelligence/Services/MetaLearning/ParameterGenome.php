<?php

namespace App\Modules\Intelligence\Services\MetaLearning;

/**
 * DNA for simulation environment parameters.
 * Evolving this leads to "Intelligence Explosion" by optimizing for emergent complexity.
 */
class ParameterGenome
{
    public function __construct(
        public readonly array $worldConfig,
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'world_config' => $this->worldConfig,
            'metadata' => $this->metadata,
        ];
    }
}
