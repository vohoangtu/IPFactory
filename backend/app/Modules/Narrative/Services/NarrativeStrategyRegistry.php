<?php

namespace App\Modules\Narrative\Services;

class NarrativeStrategyRegistry
{
    protected array $strategies = [];

    public function register(object $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function all(): array
    {
        return $this->strategies;
    }
}
