<?php

namespace Worldos\Simulation;

class BehaviorNode
{
    private int $id = 0;
    private string $name = '';
    private int|string $actionType = 0;

    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getId(): int { return $this->id; }

    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getName(): string { return $this->name; }

    public function setActionType(int|string $actionType): static { $this->actionType = $actionType; return $this; }
    public function getActionType(): int|string { return $this->actionType; }
}
