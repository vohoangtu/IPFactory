<?php

namespace App\Modules\Intelligence\Domain;

/**
 * Value Object for tracking historical stats of an Actor.
 * Kept in ActorState->metrics['behavior_stats'].
 */
final class BehaviorStats
{
    public function __construct(
        public readonly int $battlesWon = 0,
        public readonly int $battlesJoined = 0,
        public readonly int $researchActions = 0,
        public readonly int $tradeActions = 0,
        public readonly int $spiritualActions = 0,
        public readonly int $crimeActions = 0,
        public readonly int $leadActions = 0,
        public readonly int $survivalCycles = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            battlesWon: $data['battles_won'] ?? 0,
            battlesJoined: $data['battles_joined'] ?? 0,
            researchActions: $data['research_actions'] ?? 0,
            tradeActions: $data['trade_actions'] ?? 0,
            spiritualActions: $data['spiritual_actions'] ?? 0,
            crimeActions: $data['crime_actions'] ?? 0,
            leadActions: $data['lead_actions'] ?? 0,
            survivalCycles: $data['survival_cycles'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'battles_won' => $this->battlesWon,
            'battles_joined' => $this->battlesJoined,
            'research_actions' => $this->researchActions,
            'trade_actions' => $this->tradeActions,
            'spiritual_actions' => $this->spiritualActions,
            'crime_actions' => $this->crimeActions,
            'lead_actions' => $this->leadActions,
            'survival_cycles' => $this->survivalCycles,
        ];
    }

    public function multiply(float $factor): self
    {
        return new self(
            battlesWon: (int)round($this->battlesWon * $factor),
            battlesJoined: (int)round($this->battlesJoined * $factor),
            researchActions: (int)round($this->researchActions * $factor),
            tradeActions: (int)round($this->tradeActions * $factor),
            spiritualActions: (int)round($this->spiritualActions * $factor),
            crimeActions: (int)round($this->crimeActions * $factor),
            leadActions: (int)round($this->leadActions * $factor),
            survivalCycles: (int)round($this->survivalCycles * $factor)
        );
    }

    /**
     * Compute normalized versions for score functions.
     */
    public function getNorm(string $key): float
    {
        $total = max(1, $this->battlesJoined + $this->researchActions + $this->tradeActions + $this->spiritualActions + $this->crimeActions + $this->leadActions);
        
        return match($key) {
            'battles_norm' => $this->battlesJoined / $total,
            'research_norm' => $this->researchActions / $total,
            'trade_norm' => $this->tradeActions / $total,
            'spiritual_norm' => $this->spiritualActions / $total,
            'crime_norm' => $this->crimeActions / $total,
            'lead_norm' => $this->leadActions / $total,
            default => 0.0,
        };
    }
}
