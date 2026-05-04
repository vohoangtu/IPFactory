<?php

namespace Worldos\Simulation;

class BehaviorGraph
{
    private string $archetype = '';
    private array $nodes = [];
    private array $transitions = [];

    public function setArchetype(string $archetype): static { $this->archetype = $archetype; return $this; }
    public function getArchetype(): string { return $this->archetype; }

    public function setNodes(array $nodes): static { $this->nodes = $nodes; return $this; }
    public function getNodes(): array { return $this->nodes; }

    public function setTransitions(array $transitions): static { $this->transitions = $transitions; return $this; }
    public function getTransitions(): array { return $this->transitions; }
}
