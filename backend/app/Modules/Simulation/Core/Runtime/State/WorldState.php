<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

/**
 * WorldState – A structured wrapper for the Universe state_vector.
 *
 * Provides a standardized contract for all ~100 engines.
 */
class WorldState
{
    use WorldStateAccessors;

    /** @var \App\Modules\Intelligence\Entities\ActorEntity[] */
    protected array $actorEntities = [];

    /** @var \App\Modules\Institutions\Entities\InstitutionalEntity[] */
    protected array $institutionalEntities = [];

    /** @var \App\Modules\World\Entities\ResourceEntity[] */
    protected array $resourceEntities = [];

    /** @var \App\Modules\Intelligence\Entities\IdeaEntity[] */
    protected array $ideaEntities = [];

    /** @var \App\Modules\Narrative\Models\Chronicle[] */
    protected array $recentChronicles = [];

    /** @var \App\Modules\Institutions\Models\SupremeEntity[] */
    protected array $supremeEntities = [];

    /** @var bool */
    protected bool $isObserved = false;

    public function __construct(
        protected array $data = [],
        public array $neighboring_realities = [],
        public array $legacy_data = [],
        public array $hyperspace_vector = [],
        public array $nested_realities = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return WorldStateSnapshot::fromArray($data);
    }

    public function toArray(): array
    {
        return WorldStateSnapshot::toArray($this);
    }

    // --- Data access for WorldStateSnapshot delegation ---
    public function getData(): array
    {
        return $this->data;
    }
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    // --- COSMIC LAYER ---
    public function getCosmic(): array
    {
        return $this->data['cosmic'] ?? [];
    }
    public function setCosmic(array $val): void
    {
        $this->data['cosmic'] = $val;
    }

    // --- PLANETARY LAYER ---
    public function getPlanetary(): array
    {
        return $this->data['planetary'] ?? [];
    }
    public function setPlanetary(array $val): void
    {
        $this->data['planetary'] = $val;
    }

    // --- ECOSYSTEM LAYER ---
    public function getEcosystem(): array
    {
        return $this->data['ecosystem'] ?? [];
    }
    public function setEcosystem(array $val): void
    {
        $this->data['ecosystem'] = $val;
    }

    // --- ACTOR LAYER ---
    public function getActors(): array
    {
        return $this->data['actors'] ?? [];
    }
    public function setActors(array $val): void
    {
        $this->data['actors'] = $val;
    }

    // --- CIVILIZATION LAYER ---
    public function getCivilization(): array
    {
        return $this->data['civilization'] ?? [];
    }
    public function setCivilization(array $val): void
    {
        $this->data['civilization'] = $val;
    }

    public function getFields(): array
    {
        return $this->data['fields'] ?? [];
    }
    public function setFields(array $val): void
    {
        $this->data['fields'] = $val;
    }

    public function getZones(): array
    {
        return $this->data['zones'] ?? [];
    }
    public function setZones(array $val): void
    {
        $this->data['zones'] = $val;
    }

    public function getZoneMemory(int $zoneId): array
    {
        return $this->get("memory.zones.{$zoneId}", []);
    }
    public function setZoneMemory(int $zoneId, array $memory): void
    {
        $this->set("memory.zones.{$zoneId}", $memory);
    }

    public function getCivilizations(): array
    {
        return $this->get('civilizations', []);
    }
    public function getKnowledge(): array
    {
        return $this->get('civilization.knowledge_graph', []);
    }

    // --- META LAYER ---
    public function getTimeline(): array
    {
        return $this->data['timeline'] ?? [];
    }
    public function setTimeline(array $val): void
    {
        $this->data['timeline'] = $val;
    }

    /** @return \App\Modules\Intelligence\Entities\ActorEntity[] */
    public function getActorEntities(): array
    {
        return $this->actorEntities;
    }

    /** @param \App\Modules\Intelligence\Entities\ActorEntity[] $entities */
    public function setActorEntities(array $entities): void
    {
        $this->actorEntities = $entities;
    }

    /**
     * Remove an actor from the local entities collection to force reload.
     */
    public function forgetActor(int $actorId): void
    {
        $this->actorEntities = array_filter(
            $this->actorEntities,
            fn ($a) => (int)$a->id !== $actorId
        );
    }

    /** @return \App\Modules\Institutions\Entities\InstitutionalEntity[] */
    public function getInstitutionalEntities(): array
    {
        return $this->institutionalEntities;
    }

    /** @param \App\Modules\Institutions\Entities\InstitutionalEntity[] $entities */
    public function setInstitutionalEntities(array $entities): void
    {
        $this->institutionalEntities = $entities;
    }

    /** @return \App\Modules\World\Entities\ResourceEntity[] */
    public function getResourceEntities(): array
    {
        return $this->resourceEntities;
    }

    /** @param \App\Modules\World\Entities\ResourceEntity[] $entities */
    public function setResourceEntities(array $entities): void
    {
        $this->resourceEntities = $entities;
    }

    /** @return \App\Modules\Intelligence\Entities\IdeaEntity[] */
    public function getIdeaEntities(): array
    {
        return $this->ideaEntities;
    }

    /** @param \App\Modules\Intelligence\Entities\IdeaEntity[] $entities */
    public function setIdeaEntities(array $entities): void
    {
        $this->ideaEntities = $entities;
    }

    /** @return \App\Modules\Narrative\Models\Chronicle[] */
    public function getRecentChronicles(): array
    {
        return $this->recentChronicles;
    }

    /** @param \App\Modules\Narrative\Models\Chronicle[] $chronicles */
    public function setRecentChronicles(array $chronicles): void
    {
        $this->recentChronicles = $chronicles;
    }

    /** @return \App\Modules\Institutions\Models\SupremeEntity[] */
    public function getSupremeEntities(): array
    {
        return $this->supremeEntities;
    }

    /** @param \App\Modules\Institutions\Models\SupremeEntity[] $entities */
    public function setSupremeEntities(array $entities): void
    {
        $this->supremeEntities = $entities;
    }

    public function getTick(): int
    {
        return (int)$this->get('tick', 0);
    }
    public function getUniverseId(): int
    {
        return (int)$this->get('universe_id', 0);
    }

    public function isObserved(): bool
    {
        return $this->isObserved;
    }
    public function setIsObserved(bool $val): void
    {
        $this->isObserved = $val;
    }

    // --- HELPERS ---
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        data_set($this->data, $key, $value);
    }

    /**
     * Compute the difference between current data and an original context array.
     * Returns a flat array of 'dot.key' => value for scalar changes,
     * and special handling for zones/agents if needed.
     */
    public function getDiff(array $originalData): array
    {
        return WorldStateSnapshot::getDiff($this, $originalData);
    }

    public function getEntropy(): float
    {
        return (float)$this->get('entropy', 0.0);
    }
    public function setEntropy(float $val): void
    {
        $this->set('entropy', $val);
    }

    public function getStabilityIndex(): float
    {
        return (float)$this->get('stability_index', 1.0);
    }
    public function setStabilityIndex(float $val): void
    {
        $this->set('stability_index', $val);
    }

    /**
     * V10: Update a global field safely by adding a delta.
     */
    public function updateField(string $fieldName, float $delta, string $reason = ''): void
    {
        $fields = $this->getFields();
        $current = (float)($fields[$fieldName] ?? 0.0);
        $fields[$fieldName] = max(0.0, min(1.0, $current + $delta));
        $this->setFields($fields);

        \Illuminate\Support\Facades\Log::debug("WorldState: Field '{$fieldName}' mutated by {$delta} due to: {$reason}. New value: {$fields[$fieldName]}");
    }

    /**
     * Phase 4: Create an immutable snapshot copy of the current state for rollback purposes.
     * Use before a risky resolution step so state can be restored on failure.
     */
    public function snapshot(): self
    {
        return WorldStateSnapshot::snapshot($this);
    }

    /**
     * Phase 4: Restore internal state from a previously taken snapshot.
     */
    public function restoreFrom(self $snapshot): void
    {
        WorldStateSnapshot::restoreFrom($this, $snapshot);
    }

    public function getScars(): array
    {
        return (array)$this->get('scars', []);
    }
    public function setScars(array $scars): void
    {
        $this->set('scars', $scars);
    }

    public function getActiveAttractor(): string
    {
        return (string)$this->get('active_attractor', 'none');
    }
    public function setActiveAttractor(string $val): void
    {
        $this->set('active_attractor', $val);
    }

    public function getTopology(): array
    {
        return (array)$this->get('topology', []);
    }
    public function setTopology(array $val): void
    {
        $this->set('topology', $val);
    }

    public function getPreviousAttractor(): string
    {
        return (string)$this->get('previous_attractor', 'none');
    }
    public function setPreviousAttractor(string $val): void
    {
        $this->set('previous_attractor', $val);
    }

    public function getAttractorStability(): float
    {
        return (float)$this->get('attractor_stability', 1.0);
    }
    public function setAttractorStability(float $val): void
    {
        $this->set('attractor_stability', $val);
    }

    public function getPressures(): array
    {
        return (array)$this->get('pressures', []);
    }
    public function setPressures(array $val): void
    {
        $this->set('pressures', $val);
    }

    public function getNeighboringRealities(): array
    {
        return $this->neighboring_realities;
    }

    public function setNeighboringRealities(array $realities): void
    {
        $this->neighboring_realities = $realities;
    }

    public function getLegacyData(): array
    {
        return $this->legacy_data;
    }

    public function setLegacyData(array $data): void
    {
        $this->legacy_data = $data;
    }

    /**
     * Phase 65: Hyperspace Vector (V9)
     */
    public function getHyperspaceVector(): array
    {
        return $this->hyperspace_vector;
    }

    public function setHyperspaceVector(array $vector): void
    {
        $this->hyperspace_vector = $vector;
    }

    /**
     * Phase 66: Infinite Recursion (Nested Realities)
     */
    public function getNestedRealities(): array
    {
        return $this->nested_realities;
    }

    public function setNestedRealities(array $nested): void
    {
        $this->nested_realities = $nested;
    }

    public function pushNestedReality(array $stateData): void
    {
        $this->nested_realities[] = [
            'layer' => count($this->nested_realities) + 1,
            'data' => $stateData,
            'leakage_factor' => 0.01
        ];
    }

    // --- COMPATIBILITY ALIASES (Doc §5 legacy support) ---
    public function getStateVector(): array
    {
        return $this->data;
    }
    public function getMetrics(): array
    {
        return (array)$this->get('metrics', []);
    }
    public function getStateVectorKey(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return $this->get("metrics.{$key}", $default);
    }
}
