<?php

namespace App\Modules\Intelligence\Entities;

/**
 * Immutable Data Transfer Object for Actor's state.
 * Contains only properties, no business logic or side effects.
 */
class ActorState
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public readonly string $name,
        public readonly string $archetype,
        public readonly array $traits = [],
        public readonly array $metrics = [],
        public readonly bool $isAlive = true,
        public readonly int $generation = 1,
        public readonly ?string $biography = null,
        public readonly bool $isHeroic = false,
        public readonly ?string $heroicType = null,
        public readonly ?string $vocationId = null,
        public readonly ?int $factionId = null,
        public readonly float $loyalty = 0.5
    ) {}

    /**
     * Create a new instance with modified properties (Immutable style)
     */
    public function with(array $changes): self
    {
        return new self(
            id: array_key_exists('id', $changes) ? $changes['id'] : $this->id,
            universeId: array_key_exists('universeId', $changes) ? $changes['universeId'] : $this->universeId,
            name: array_key_exists('name', $changes) ? $changes['name'] : $this->name,
            archetype: array_key_exists('archetype', $changes) ? $changes['archetype'] : $this->archetype,
            traits: array_key_exists('traits', $changes) ? $changes['traits'] : $this->traits,
            metrics: array_key_exists('metrics', $changes) ? $changes['metrics'] : $this->metrics,
            isAlive: array_key_exists('isAlive', $changes) ? $changes['isAlive'] : $this->isAlive,
            generation: array_key_exists('generation', $changes) ? $changes['generation'] : $this->generation,
            biography: array_key_exists('biography', $changes) ? $changes['biography'] : $this->biography,
            isHeroic: array_key_exists('isHeroic', $changes) ? $changes['isHeroic'] : $this->isHeroic,
            heroicType: array_key_exists('heroicType', $changes) ? $changes['heroicType'] : $this->heroicType,
            vocationId: array_key_exists('vocationId', $changes) ? $changes['vocationId'] : $this->vocationId,
            factionId: array_key_exists('factionId', $changes) ? $changes['factionId'] : $this->factionId,
            loyalty: array_key_exists('loyalty', $changes) ? $changes['loyalty'] : $this->loyalty
        );
    }

    public function getMotivationProfile(): array
    {
        return [
            'survival'     => ($this->traits['Resilience'] ?? 0.5),
            'reproduction' => ($this->traits['Vitality'] ?? 0.5),
            'wealth'       => ($this->traits['Pragmatism'] ?? 0.5) * 0.7 + ($this->traits['Ambition'] ?? 0.5) * 0.3,
            'power'        => ($this->traits['Dominance'] ?? 0.5) * 0.6 + ($this->traits['Coercion'] ?? 0.5) * 0.4,
            'knowledge'    => ($this->traits['Curiosity'] ?? 0.5),
            'meaning'      => ($this->traits['Hope'] ?? 0.5) * 0.7 + (1 - ($this->traits['Dogmatism'] ?? 0.5)) * 0.3,
            'status'       => ($this->traits['Pride'] ?? 0.5) * 0.8 + ($this->traits['Dominance'] ?? 0.5) * 0.2,
            'belonging'    => ($this->traits['Solidarity'] ?? 0.5) * 0.4 + ($this->traits['Conformity'] ?? 0.5) * 0.3 + ($this->traits['Loyalty'] ?? 0.3),
        ];
    }

    public function getPhysicHealth(): float
    {
        return (float)($this->traits['Vitality'] ?? 0.5);
    }

    public function getInfluence(): float
    {
        return (float)($this->metrics['influence'] ?? 0.0);
    }
}
