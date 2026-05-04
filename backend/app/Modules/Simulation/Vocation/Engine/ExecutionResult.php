<?php

namespace App\Modules\Simulation\Vocation\Engine;

class ExecutionResult
{
    public array $effects = [];
    public array $logs = [];

    public function addEffect(array $effect): void
    {
        $this->effects[] = $effect;
    }

    public function addLog(string $message): void
    {
        $this->logs[] = $message;
    }

    /**
     * Merge multiple results (important for chain reactions)
     */
    public function merge(self $other): void
    {
        $this->effects = array_merge($this->effects, $other->effects);
        $this->logs = array_merge($this->logs, $other->logs);
    }
}
