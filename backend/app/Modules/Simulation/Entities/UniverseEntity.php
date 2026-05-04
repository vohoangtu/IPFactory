<?php

namespace App\Modules\Simulation\Entities;

class UniverseEntity
{
    public function __construct(
        public readonly int $id,
        public readonly int $worldId,
        public readonly string $name,
        public int $currentTick,
        public float $entropy,
        public float $stabilityIndex,
        public float $observationLoad,
        public array $stateVector,
        public array $kernelGenome = [],
        public string $status = 'active',
        public float $structuralCoherence = 1.0,
        public float $observerBonus = 0.0,
        public float $fitnessScore = 0.0
    ) {}

    public static function create(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            worldId: (int) $data['world_id'],
            name: $data['name'],
            currentTick: (int) $data['current_tick'],
            entropy: (float) $data['entropy'],
            stabilityIndex: (float) $data['stability_index'],
            observationLoad: (float) $data['observation_load'],
            stateVector: $data['state_vector'] ?? [],
            kernelGenome: $data['kernel_genome'] ?? [],
            status: $data['status'] ?? 'active',
            structuralCoherence: (float) ($data['structural_coherence'] ?? 1.0),
            observerBonus: (float) ($data['observer_bonus'] ?? 0.0),
            fitnessScore: (float) ($data['fitness_score'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'world_id' => $this->worldId,
            'name' => $this->name,
            'current_tick' => $this->currentTick,
            'entropy' => $this->entropy,
            'stability_index' => $this->stabilityIndex,
            'observation_load' => $this->observationLoad,
            'state_vector' => $this->stateVector,
            'kernel_genome' => $this->kernelGenome,
            'status' => $this->status,
            'structural_coherence' => $this->structuralCoherence,
            'observer_bonus' => $this->observerBonus,
            'fitness_score' => $this->fitnessScore,
        ];
    }

    public function applyObservationInterference(float $intensity): void
    {
        $this->observationLoad += $intensity;
        $this->entropy = max(0.0, $this->entropy - ($intensity * 0.05));
        $this->stabilityIndex = min(1.0, $this->stabilityIndex + ($intensity * 0.1));
        
        $this->stateVector['entropy'] = $this->entropy;
        $this->stateVector['stability_index'] = $this->stabilityIndex;
    }

    public function decayObservationLoad(float $decay): void
    {
        $this->observationLoad = max(0.0, $this->observationLoad - $decay);
    }
}
