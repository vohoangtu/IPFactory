<?php

namespace App\Modules\Simulation\Vocation\Entities;

class VocationEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public int $tier,
        public array $elementAffinity,
        public array $requirements = [],
        public ?string $evolvesTo = null,
        public array $metadata = []
    ) {}

    /**
     * Check if an actor meets the requirements for this vocation.
     */
    public function isEligible(array $actorStats): bool
    {
        foreach ($this->requirements as $stat => $value) {
            if (($actorStats[$stat] ?? 0) < $value) {
                return false;
            }
        }
        return true;
    }
}
